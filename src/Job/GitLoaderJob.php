<?php

namespace ManageGit\Job;

use Base3\Worker\Api\IJob;
use Base3\Configuration\Api\IConfiguration;
use Base3\Api\ICheck;

class GitLoaderJob implements IJob, ICheck {

	private $configuration;
	private $nextRun = 6 * 3600;

	public function __construct(IConfiguration $configuration) {
		$this->configuration = $configuration;
	}

	// Implementation of IBase

	public function getName(): string {
		return 'gitloaderjob';
	}

	// Implementation of IJob

	public function isActive() {
		return true;
	}

	public function getPriority() {
		return 1;
	}

	public function go() {
		$dataDir = $this->getDataDir();
		if (!strlen($dataDir)) return 'data dir undefined';

		$nextRunFile = $this->getNextRunFile();
		$now = time();
		$nextRun = is_file($nextRunFile) ? intval(file_get_contents($nextRunFile)) : 0;
		if ($now < $nextRun) return 'skipped (next run: ' . date('c', $nextRun) . ')';
		file_put_contents($nextRunFile, strval($now + $this->nextRun));

		$result = $this->getAccounts();
		return $result;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			'managegit_dir_defined' => strlen($this->getDataDir()) ? 'Ok' : 'managegit dir not defined',
			'managegit_dir_writable' => is_writable($this->getDataDir()) ? 'Ok' : 'managegit dir not writable'
		);
	}

	// Private methods

	private function getDataDir() {
		$directories = $this->configuration->get('directories');
		return isset($directories['data'])
			? $directories['data'] . DIRECTORY_SEPARATOR . 'managegit' . DIRECTORY_SEPARATOR
			: '';
	}

	private function getNextRunFile(): string {
		return $this->getDataDir() . 'nextrun';
	}

	private function request($url, $token, $platform = 'github') {
		$headers = [
			'User-Agent: Base3Manager',
			'Accept: application/json',
		];
		if ($platform === 'github') {
			$headers[] = 'Authorization: token ' . $token;
		} else if ($platform === 'gitlab') {
			$headers[] = 'PRIVATE-TOKEN: ' . $token;
		}

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $headers,
		]);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpCode !== 200) return [];

		$data = json_decode($response, true);
		return is_array($data) ? $data : [];
	}

	private function getAccounts(): string {
		$dataDir = $this->getDataDir();
		if (!strlen($dataDir)) return 'data dir undefined';

		$configFile = $dataDir . 'managegit-config.ini';
		$accounts = parse_ini_file($configFile, true);

		$repositories = [];
		foreach ($accounts as $key => $account) {
			$platform = $account['platform'] ?? 'github';

			$reposResult = $this->getRepositories($account['token'], $account['endpoint'], $platform);
			if ($reposResult['error']) continue;

			foreach ($reposResult['data'] as $repo) {
				$repo = $this->filterRepositoryData($repo, $platform);

				$branches = $this->getBranches(
					$repo,
					$account['token'],
					$account['endpoint'],
					$platform
				);
				$repo['branches'] = $branches;
				$repo['base3config'] = $key;
				$repositories[] = $repo;
			}
		}

		file_put_contents($dataDir . 'repositories.json', json_encode($repositories, JSON_PRETTY_PRINT));
		return 'done';
	}

	private function getRepositories($token, $endpoint, $platform) {
		$repositories = [];
		$page = 1;
		$perPage = 100;

		do {
			if ($platform === 'github') {
				$url = $endpoint . '/user/repos?per_page=' . $perPage . '&page=' . $page;
			} else if ($platform === 'gitlab') {
				$url = $endpoint . '/projects?membership=true&simple=true&per_page=' . $perPage . '&page=' . $page;
			} else {
				return [ 'error' => 1, 'msg' => 'Unsupported platform', 'data' => [] ];
			}

			$data = $this->request($url, $token, $platform);
			if (!is_array($data)) break;

			// gitlab
			foreach ($data as $key => $repo) {
				if (isset($repo['web_url']) && !isset($repo['html_url']))
					$data[$key]['html_url'] = $repo['web_url'];
			}

			$repositories = array_merge($repositories, $data);
			$page++;
		} while (count($data) === $perPage);

		return [ 'error' => 0, 'msg' => '', 'data' => $repositories ];
	}

	private function getBranches($repo, $token, $endpoint, $platform) {
		$branches = [];

		if ($platform === 'github') {
			$owner = $repo['owner']['login'];
			$repoName = $repo['name'];
			$url = $endpoint . "/repos/$owner/$repoName/branches";
		} else if ($platform === 'gitlab') {
			$projectId = urlencode($repo['id']);
			$url = $endpoint . "/projects/$projectId/repository/branches";
		} else {
			return [];
		}

		$branchData = $this->request($url, $token, $platform);

		foreach ($branchData as $branch) {
			$commitInfo = [];

			if ($platform === 'github') {
				if (!empty($branch['commit']['url'])) {
					$commitInfo = $this->request($branch['commit']['url'], $token, $platform);
				}
			} else if ($platform === 'gitlab') {
				$commitInfo = $branch['commit'] ?? [];
			}

			$branches[] = [
				'name' => $branch['name'],
				'is_default' => $branch['name'] === ($repo['default_branch'] ?? ''),
				'protected' => $branch['protected'] ?? false,
				'last_commit' => $this->extractCommitInfo($commitInfo, $platform),
			];
		}

		return $branches;
	}

	private function extractCommitInfo($commit, $platform) {
		if ($platform === 'github') {
			return [
				'sha' => $commit['sha'] ?? '',
				'message' => $commit['commit']['message'] ?? '',
				'date' => $commit['commit']['committer']['date'] ?? '',
				'author' => [
					'name' => $commit['commit']['author']['name'] ?? '',
					'email' => $commit['commit']['author']['email'] ?? '',
				],
				'url' => $commit['html_url'] ?? $commit['url'] ?? '',
			];
		} else if ($platform === 'gitlab') {
			return [
				'sha' => $commit['id'] ?? '',
				'message' => $commit['message'] ?? '',
				'date' => $commit['committed_date'] ?? '',
				'author' => [
					'name' => $commit['author_name'] ?? '',
					'email' => $commit['author_email'] ?? '',
				],
				'url' => $commit['web_url'] ?? '',
			];
		}
		return [];
	}

	private function filterRepositoryData(array $repo, string $platform): array {
		if ($platform === 'github') {
			if (isset($repo['owner'])) {
				$repo['owner'] = $this->filterUserData($repo['owner']);
			}
			$keysToRemove = [
				'forks_url', 'keys_url', 'collaborators_url', 'teams_url', 'hooks_url', 'issue_events_url',
				'events_url', 'assignees_url', 'branches_url', 'tags_url', 'blobs_url', 'git_tags_url',
				'git_refs_url', 'trees_url', 'statuses_url', 'languages_url', 'stargazers_url',
				'contributors_url', 'subscribers_url', 'subscription_url', 'commits_url', 'git_commits_url',
				'comments_url', 'issue_comment_url', 'contents_url', 'compare_url', 'merges_url',
				'archive_url', 'downloads_url', 'issues_url', 'pulls_url', 'milestones_url',
				'notifications_url', 'labels_url', 'releases_url', 'deployments_url',
			];
			foreach ($keysToRemove as $key) unset($repo[$key]);
		} else if ($platform === 'gitlab') {
			// hier eventuell noch gitlab-spezifische Felder rausfiltern (optional)
		}
		return $repo;
	}

	private function filterUserData(array $user): array {
		$keysToRemove = [
			'followers_url', 'following_url', 'gists_url', 'starred_url', 'subscriptions_url',
			'organizations_url', 'repos_url', 'events_url', 'received_events_url',
		];
		foreach ($keysToRemove as $key) unset($user[$key]);
		return $user;
	}
}


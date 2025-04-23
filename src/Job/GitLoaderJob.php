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

		$result = $this->loadRepositories();
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

private function getNextRunFile(): string {
    return $this->getDataDir() . 'nextrun';
}

    private function loadRepositories(): string {
        $dataDir = $this->getDataDir();
        if (!strlen($dataDir)) return 'data dir undefined';

        $configFile = $dataDir . 'managegit-config.ini';
        $accounts = parse_ini_file($configFile, true);

        $repositories = [];
        foreach ($accounts as $key => $account) {
            $reposResult = $this->getRepositories($account['token'], $account['endpoint']);
            if ($reposResult['error']) continue;

            foreach ($reposResult['data'] as $repo) {
                $repo = $this->filterRepositoryData($repo);

                $branches = $this->getBranches(
                    $repo['owner']['login'],
                    $repo['name'],
                    $account['token'],
                    $account['endpoint'],
                    $repo['default_branch']
                );
                $repo['branches'] = $branches;
                $repo['base3config'] = $key;
                $repositories[] = $repo;
            }
        }

        file_put_contents($dataDir . 'repositories.json', json_encode($repositories, JSON_PRETTY_PRINT));
        return 'done';
    }

private function filterRepositoryData(array $repo): array {
    // Owner filtern
    if (isset($repo['owner'])) {
        $repo['owner'] = $this->filterUserData($repo['owner']);
    }

    // Liste der zu entfernenden Felder aus dem Repo
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

	private function getDataDir() {
		$directories = $this->configuration->get('directories');
		return isset($directories['data'])
			? $directories['data'] . DIRECTORY_SEPARATOR . 'managegit' . DIRECTORY_SEPARATOR
			: '';
	}

    private function getRepositories($token, $endpoint) {
        $repositories = [];
        $page = 1;
        $perPage = 100;

        do {
            $url = $endpoint . '/user/repos?per_page=' . $perPage . '&page=' . $page;

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Base3Manager',
                    'Authorization: token ' . $token,
                    'Accept: application/vnd.github+json',
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return [
                    'error' => 1,
                    'msg' => "Fehler beim Abrufen der Repositories (HTTP $httpCode):\n" . $response,
                    'data' => null,
                ];
            }

            $data = json_decode($response, true);
            if (!is_array($data)) {
                return [
                    'error' => 1,
                    'msg' => 'Unerwartetes Antwortformat von GitHub',
                    'data' => null,
                ];
            }

            $repositories = array_merge($repositories, $data);
            $page++;

        } while (count($data) === $perPage);

        return [
            'error' => 0,
            'msg' => '',
            'data' => $repositories,
        ];
    }

    private function getBranches($owner, $repo, $token, $endpoint, $defaultBranchName) {
        $url = $endpoint . "/repos/$owner/$repo/branches";
        $branches = [];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Base3Manager',
                'Authorization: token ' . $token,
                'Accept: application/vnd.github+json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) return [];

        $branchData = json_decode($response, true);
        if (!is_array($branchData)) return [];

        foreach ($branchData as $branch) {
            $commitInfo = $this->getCommitInfo($branch['commit']['url'], $token);
            $branches[] = [
                'name' => $branch['name'],
                'is_default' => ($branch['name'] === $defaultBranchName),
                'protected' => $branch['protected'],
                'last_commit' => $commitInfo,
            ];
        }

        return $branches;
    }

    private function getCommitInfo($commitUrl, $token) {
        $ch = curl_init($commitUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Base3Manager',
                'Authorization: token ' . $token,
                'Accept: application/vnd.github+json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) return [];

        $commit = json_decode($response, true);
        if (!is_array($commit)) return [];

        return [
            'sha' => $commit['sha'] ?? null,
            'message' => $commit['commit']['message'] ?? '',
            'date' => $commit['commit']['committer']['date'] ?? '',
            'author' => [
                'name' => $commit['commit']['author']['name'] ?? '',
                'email' => $commit['commit']['author']['email'] ?? '',
            ],
            'url' => $commit['html_url'] ?? $commit['url'] ?? '',
        ];
    }
}


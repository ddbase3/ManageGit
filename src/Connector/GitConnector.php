<?php

namespace ManageGit\Connector;

use Base3\Api\IBase;
use Base3\Api\IOutput;
use Base3\Api\IRequest;
use Base3\Configuration\Api\IConfiguration;
use Base3\Accesscontrol\Api\IAccesscontrol;

class GitConnector implements IOutput {

    private $defaultPerPage = 10;

    public function __construct(
        private readonly IAccesscontrol $accesscontrol,
        private readonly IConfiguration $configuration,
        private readonly IRequest $request
    ) {}

    // Implementation of IBase

    public function getName() {
        return "gitconnector";
    }

    // Implementation of IOutput

    public function getOutput($out = "html") {
        if ($out !== "json") return null;
        if (!$this->accesscontrol->getUserId()) return null;

        $directories = $this->configuration->get('directories');
        $datadir = $directories['data'];

        $file = $datadir . DIRECTORY_SEPARATOR . 'managegit' . DIRECTORY_SEPARATOR . 'repositories.json';
        if (!file_exists($file)) {
            return json_encode(['error' => true, 'message' => 'File not found']);
        }

        $json = file_get_contents($file);
        $repos = json_decode($json, true);
        if (!is_array($repos)) {
            return json_encode(['error' => true, 'message' => 'Invalid JSON format']);
        }

        // topics
        foreach ($repos as &$repo) {
            $topics = $repo['topics'] ?? [];
            $repo['base3utf8'] = in_array('base3-utf8', $topics) ? 'true' : 'false';
            $repo['base3site'] = in_array('base3-site', $topics) ? 'true' : 'false';
            $repo['base3manager'] = in_array('base3-manager', $topics) ? 'true' : 'false';
            $repo['base3public'] = in_array('base3-public', $topics) ? 'true' : 'false';
            $repo['base3private'] = in_array('base3-private', $topics) ? 'true' : 'false';
            $repo['base3packagist'] = in_array('base3-packagist', $topics) ? 'true' : 'false';
            $repo['base3start'] = in_array('base3-start', $topics) ? 'true' : 'false';
        }
        unset($repo);

        // Boolesche Werte in Strings umwandeln
        array_walk_recursive($repos, function (&$value, $key) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false'; // Umwandlung in Strings 'true'/'false'
            }
        });

        // Sortierung
        $sort = $this->request->get('sort', 'name');
        $direction = strtolower($this->request->get('direction', 'asc'));
        usort($repos, function ($a, $b) use ($sort, $direction) {
            $aVal = strtolower($a[$sort] ?? '');
            $bVal = strtolower($b[$sort] ?? '');
            return ($direction === 'desc' ? -1 : 1) * strcmp($aVal, $bVal);
        });

	// Filter
        $filters = $this->request->get('filter', []);
        $repos = array_filter($repos, function ($repo) use ($filters) {
            foreach ($filters as $key => $val) {
                if (!isset($repo[$key])) return false;
                if (stripos($repo[$key], $val) === false) return false;
            }
            return true;
        });

        // Paging
        $total = count($repos);
        $perPage = $this->defaultPerPage;
        $totalPages = ceil($total / $perPage);
        $page = min(max(1, intval($this->request->get('page', 1))), $totalPages);
        $offset = ($page - 1) * $perPage;
        $pagedRepos = array_slice($repos, $offset, $perPage);

        return json_encode([
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'repos' => $pagedRepos
        ]);
    }

    public function getHelp() {
        return "Liefert eine Liste der Git-Repositories als JSON (aus repositories.json). Optional: ?sort=name&direction=asc&page=1";
    }
}


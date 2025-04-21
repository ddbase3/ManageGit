<?php

namespace ManageGit\Connector;

use Base3\Api\IBase;
use Base3\Api\IOutput;
use Base3\Configuration\Api\IConfiguration;
use Base3\Accesscontrol\Api\IAccesscontrol;

class GitConnector implements IOutput {

    private $configuration;
    private $accesscontrol;
    private $defaultPerPage = 10;

    public function __construct(
        IAccesscontrol $accesscontrol,
        IConfiguration $configuration
    ) {
        $this->accesscontrol = $accesscontrol;
        $this->configuration = $configuration;
    }

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

        // Sortierung
        $sort = $_GET['sort'] ?? 'name';
        $direction = strtolower($_GET['direction'] ?? 'asc');
        usort($repos, function ($a, $b) use ($sort, $direction) {
            $aVal = strtolower($a[$sort] ?? '');
            $bVal = strtolower($b[$sort] ?? '');
            return ($direction === 'desc' ? -1 : 1) * strcmp($aVal, $bVal);
        });

	// Filter
        $filters = $_GET['filter'] ?? [];
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
        $page = min(max(1, intval($_GET['page'] ?? 1)), $totalPages);
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


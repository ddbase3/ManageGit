<?php

namespace ManageGit\Connector;

use Base3\Api\IBase;
use Base3\Api\IOutput;
use Base3\Configuration\Api\IConfiguration;
use Base3\Accesscontrol\Api\IAccesscontrol;

class GitActiveBranchConnector implements IOutput {

    public function __construct(
        private IAccesscontrol $accesscontrol,
        private IConfiguration $configuration
    ) {}

    // Implementation of IBase

    public function getName() {
        return 'gitactivebranchconnector';
    }

    // Implementation of IOutput

    public function getOutput($out = "html") {
        if ($out !== "json") return null;
        if (!$this->accesscontrol->getUserId()) return null;

        // Datenquelle bestimmen
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

        // Zähle Default-Branch-Namen
        $counts = [];
        foreach ($repos as $repo) {
            $defaultBranch = $repo['default_branch'] ?? null;
            if ($defaultBranch) {
                if (!isset($counts[$defaultBranch])) {
                    $counts[$defaultBranch] = 0;
                }
                $counts[$defaultBranch]++;
            }
        }

        // Formatieren für Diagramm: array mit label & count
        $result = [];
        foreach ($counts as $branch => $count) {
            $result[] = [
                'branch' => $branch,
                'count' => $count
            ];
        }

        usort($result, function($a, $b) {
            if ($a['branch'] == $b['branch']) return 0;
            return $a['branch'] < $b['branch'] ? -1 : 1;
        });

        return json_encode($result);
    }

    public function getHelp() {
        return "Gibt die Anzahl Repositories pro Default-Branch aus. Format: [{branch: 'main', count: 3}, ...]";
    }
}


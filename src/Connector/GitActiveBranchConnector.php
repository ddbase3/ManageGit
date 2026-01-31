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

    public static function getName(): string {
        return 'gitactivebranchconnector';
    }

    // Implementation of IOutput

    public function getOutput(string $out = 'html', bool $final = false): string {
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

        // Zähle nur relevante Branches
        $counts = [];
        foreach ($repos as $repo) {
            $defaultBranch = $repo['default_branch'] ?? null;
            if ($defaultBranch && $this->isValidBranch($defaultBranch)) {
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

        // Sortiere nach Branch-Namen
        usort($result, function($a, $b) {
            if ($a['branch'] == $b['branch']) return 0;
            return $a['branch'] < $b['branch'] ? -1 : 1;
        });

        return json_encode($result);
    }

    public function getHelp(): string {
        return "Gibt die Anzahl Repositories pro Default-Branch aus, für Branches wie 'main', 'master', 'v1', 'v2', '54', '54_dev', etc. Format: [{branch: 'main', count: 3}, ...]";
    }

    // Prüft, ob der Branch-Name den erlaubten Mustern entspricht
    private function isValidBranch(string $branch): bool {
        // Muster: main, master, v1, v2, ..., 54, 54_dev, 6, 6_dev, 7, 7_dev, ...
        $pattern = '/^(main|master|v\d+|\d+(_dev)?)$/';
        return preg_match($pattern, $branch) === 1;
    }
}


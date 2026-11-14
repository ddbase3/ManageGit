<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of ManageGit for BASE3 Framework.
 *
 * ManageGit provides Git repository management controls.
 * It composes existing services and repository utilities.
 * The module contributes its own manager displays.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 ***********************************************************************/

namespace Base3\ManageGit\Connector;

use Base3\CoreFoundation\Api\IBase;
use Base3\CoreFoundation\Api\IOutput;
use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\CoreFoundation\Accesscontrol\Api\IAccesscontrol;

class GitActivityConnector implements IOutput {

    public function __construct(
        private IAccesscontrol $accesscontrol,
        private IConfiguration $configuration
    ) {}

    public static function getName(): string {
        return "gitactivityconnector";
    }

    public function getOutput(string $out = 'html', bool $final = false): string {
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

        // Schritt 1: Alle last_commit-Daten im Default-Branch einsammeln
        $activityByMonth = [];

        $minDate = null;
        $maxDate = null;

        foreach ($repos as $repo) {
            $default = $repo['default_branch'] ?? null;
            $branches = $repo['branches'] ?? [];

            foreach ($branches as $branch) {
                if (($branch['name'] ?? null) === $default) {
                    $dateStr = $branch['last_commit']['date'] ?? null;
                    if (!$dateStr) continue;

                    $month = date('Y-m', strtotime($dateStr));
                    $activityByMonth[$month] = ($activityByMonth[$month] ?? 0) + 1;

                    $date = new \DateTime($month . '-01');
                    if (!$minDate || $date < $minDate) $minDate = clone $date;
                    if (!$maxDate || $date > $maxDate) $maxDate = clone $date;
                }
            }
        }

// Schritt 2: Nur die letzten 12 Monate auffüllen
$result = [];
$current = new \DateTime('first day of this month');
$current->setTime(0, 0);

// 12 Monate rückwärts, inklusive aktueller Monat
for ($i = 11; $i >= 0; $i--) {
    $month = (clone $current)->modify("-$i months")->format('Y-m');
    $result[] = [
        'month' => $month,
        'repos' => $activityByMonth[$month] ?? 0
    ];
}

        return json_encode($result);
    }

    public function getHelp(): string {
        return "Liefert eine JSON-Liste: Repositories mit letzter Aktivität (im Default-Branch) pro Monat.";
    }
}

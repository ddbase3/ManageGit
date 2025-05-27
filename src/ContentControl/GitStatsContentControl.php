<?php declare(strict_types=1);

namespace ManageGit\ContentControl;

use Base3Manager\ContentControl\AbstractContentControl;

class GitStatsContentControl extends AbstractContentControl {

        // Implementation of IBase

        public static function getName(): string {
                return "gitstatscontentcontrol";
        }

	// Implementation of AbstractContentControl

        protected function getPath(): string {
                return DIR_PLUGIN . 'ManageGit';
        }

        protected function getTemplate(): string {
                return 'ContentControl/GitStatsContentControl.php';
        }
}


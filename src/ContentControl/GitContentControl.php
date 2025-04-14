<?php declare(strict_types=1);

namespace ManageGit\ContentControl;

use Base3Manager\ContentControl\AbstractContentControl;

class GitContentControl extends AbstractContentControl {

        // Implementation of IBase

        public function getName(): string {
                return "gitcontentcontrol";
        }

	// Implementation of AbstractContentControl

        protected function getPath(): string {
                return DIR_PLUGIN . 'ManageGit';
        }

        protected function getTemplate(): string {
                return 'ContentControl/GitContentControl.php';
        }

}


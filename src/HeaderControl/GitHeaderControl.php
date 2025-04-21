<?php declare(strict_types=1);

namespace ManageGit\HeaderControl;

use Base3Manager\HeaderControl\AbstractHeaderControl;

class GitHeaderControl extends AbstractHeaderControl {

        // Implementation of IBase

        public function getName(): string {
                return "githeadercontrol";
        }

	// Implementation of AbstractHeaderControl

        protected function getPath(): string {
                return DIR_PLUGIN . 'ManageGit';
        }

        protected function getTemplate(): string {
                return 'HeaderControl/GitHeaderControl.php';
        }

}


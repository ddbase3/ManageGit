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


namespace Base3\ManageGit\HeaderControl;

use Base3\Base3Manager\HeaderControl\AbstractHeaderControl;

class GitHeaderControl extends AbstractHeaderControl {

        // Implementation of IBase

        public static function getName(): string {
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


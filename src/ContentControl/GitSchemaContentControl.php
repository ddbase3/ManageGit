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


namespace Base3\ManageGit\ContentControl;

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IDisplay;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\Base3Manager\Service\Base3Manager;
use Base3\Base3Manager\ContentControl\AbstractContentControl;

class GitSchemaContentControl extends AbstractContentControl {

	public function __construct(
		protected IMvcView $view,
		protected Base3Manager $base3manager,
		protected ILinkTargetService $linktargetservice,
		private readonly IClassMap $classmap
	) {
		parent::__construct($view, $base3manager, $linktargetservice);
	}

        // Implementation of IBase

        public static function getName(): string {
                return "gitschemacontentcontrol";
        }

	// Implementation of AbstractContentControl

        protected function getPath(): string {
                return DIR_PLUGIN . 'ManageGit';
        }

        protected function getTemplate(): string {
                return 'ContentControl/GitSchemaContentControl.php';
        }

	protected function fillView() {
		$content = 'Unable to load content.';
		$display = $this->classmap->getInstanceByInterfaceName(IDisplay::class, 'datahawkschemadisplay');
		if ($display != null) {
			$display->setData(['domain' => 'git']);
			$content = $display->getOutput();
		}
		$this->view->assign('content', $content);
	}
}


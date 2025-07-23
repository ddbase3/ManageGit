<?php declare(strict_types=1);

namespace ManageGit\ContentControl;

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3Manager\Service\Base3Manager;
use Base3Manager\ContentControl\AbstractContentControl;

class GitSchemaContentControl extends AbstractContentControl {

	public function __construct(
		protected IMvcView $view,
		protected Base3Manager $base3manager,
		private readonly IClassMap $classmap
	) {
		parent::__construct($view, $base3manager);
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


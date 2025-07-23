<?php declare(strict_types=1);

namespace ManageGit\ContentControl;

use Base3\Api\IMvcView;
use Base3Manager\ContentControl\AbstractContentControl;
use Base3Manager\Service\Base3Manager;
use Vizion\Api\IReportDisplay;

class GitReposContentControl extends AbstractContentControl {

        public function __construct(
                protected IMvcView $view,
		protected Base3Manager $base3manager,
		private readonly IReportDisplay $reportdisplay
        ) {
                parent::__construct($view, $base3manager);
        }

        // Implementation of IBase

        public static function getName(): string {
                return "gitreposcontentcontrol";
        }

	// Implementation of AbstractContentControl

        protected function getPath(): string {
                return DIR_PLUGIN . 'ManageGit';
        }

        protected function getTemplate(): string {
                return 'ContentControl/GitReposContentControl.php';
        }

	protected function fillView() {
		$this->reportdisplay->setData('gitrepos');
		$content = $this->reportdisplay->getOutput('html');
		$this->view->assign('content', $content);
	}
}

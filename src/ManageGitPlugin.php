<?php declare(strict_types=1);

namespace ManageGit;

use Base3\Api\IContainer;
use Base3\Configuration\Api\IConfiguration;
use Base3Manager\Plugin\AbstractPlugin;

class ManageGitPlugin extends AbstractPlugin {

	// Implementation of IPlugin

	public function init() {

		$this->container

			->set(self::getName(), $this, IContainer::SHARED)

			->set(
				'managegitjobs', // only in container for ICheck
				array(
					function() { return new \ManageGit\Job\GitLoaderJob($this->container->get(IConfiguration::class)); }
				));
	}

	// Implementation of ICheck

	public function checkDependencies(): array {
		return array(
			"Check" => "Ok"
		);
	}
}

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


namespace Base3\ManageGit;

use Base3\CoreFoundation\Api\IModuleRegistry;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\Base3Manager\Plugin\AbstractPlugin;
use Base3\DataHawk\Api\IReportQueryService;
use Base3\DataHawk\Api\IReportSchemaProvider;
use Base3\DataHawk\Service\DataHawkMicroservice;
use Base3\DataHawk\Schema\DataHawkSchemaMicroservice;
use Base3\Vizion\Api\IReportConfigProvider;
use Base3\Vizion\Service\FileReportConfigProvider;

class ManageGitPlugin extends AbstractPlugin {

	// Implementation of IPlugin

	public function init() {

		$this->container

			->set(self::getName(), $this, IContainer::SHARED)

			->set(
				'managegitjobs', // only in container for ICheck
				array(
					function() { return new \Base3\ManageGit\Job\GitLoaderJob($this->container->get(IConfiguration::class)); }
				))
			
			->set(
				IReportQueryService::class,
				fn($c) => new DataHawkMicroservice(
					$c->get('microserviceserializedhelper')->get('Data', IReportQueryService::class, 'datahawkmicroservice')),
				IContainer::SHARED)

			->set(
				IReportSchemaProvider::class,
				fn($c) => new DataHawkSchemaMicroservice(
					$c->get('microserviceserializedhelper')->get('Data', IReportSchemaProvider::class, 'datahawkschemamicroservice')),
				IContainer::SHARED)
				
			->set(
				IReportConfigProvider::class,
				fn($c) => new FileReportConfigProvider($c->get(IModuleRegistry::class)),
				IContainer::SHARED);
	}

	// Implementation of ICheck

	public function checkDependencies(): array {
		return array(
			"Check" => "Ok"
		);
	}
}

<?php declare(strict_types=1);

namespace ManageGit;

use Base3\Api\IModuleRegistry;
use Base3\Api\IContainer;
use Base3\Configuration\Api\IConfiguration;
use Base3Manager\Plugin\AbstractPlugin;
use DataHawk\Api\IReportQueryService;
use DataHawk\Api\IReportSchemaProvider;
use DataHawk\Service\DataHawkMicroservice;
use DataHawk\Schema\DataHawkSchemaMicroservice;
use Vizion\Api\IReportConfigProvider;
use Vizion\Service\FileReportConfigProvider;

class ManageGitPlugin extends AbstractPlugin {

	// Implementation of IPlugin

	public function init() {

		$this->container

			->set(self::getName(), $this, IContainer::SHARED)

			->set(
				'managegitjobs', // only in container for ICheck
				array(
					function() { return new \ManageGit\Job\GitLoaderJob($this->container->get(IConfiguration::class)); }
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

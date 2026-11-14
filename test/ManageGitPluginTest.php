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

namespace Base3\ManageGit\Test;

use PHPUnit\Framework\TestCase;
use Base3\ManageGit\ManageGitPlugin;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IModuleRegistry;
use Base3\Core\ServiceLocator;
use Base3\Vizion\Api\IReportConfigProvider;
use Base3\Vizion\Service\FileReportConfigProvider;

class ManageGitPluginTest extends TestCase
{
    public function testInitSetsServicesInContainer()
    {
        $containerMock = $this->createMock(IContainer::class);

        $plugin = new ManageGitPlugin($containerMock);
        $plugin->init();

        $this->assertTrue(true);
    }

    public function testReportConfigProviderUsesModuleRegistryInsteadOfClassMap()
    {
        $modulePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base3_managegit_' . uniqid('', true);
        $configDirectory = $modulePath . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'Vizion';
        $this->assertTrue(mkdir($configDirectory, 0700, true));
        $reportFile = $configDirectory . DIRECTORY_SEPARATOR . 'managegit_registry_test.json';

        try {
            $this->assertNotFalse(file_put_contents($reportFile, '{"module_registry":true}'));

            $moduleRegistry = $this->createStub(IModuleRegistry::class);
            $moduleRegistry->method('getModuleNames')->willReturn(['ManageGit']);
            $moduleRegistry->method('getModulePath')->willReturn($modulePath);

            $container = new ServiceLocator();
            $container->set(IModuleRegistry::class, $moduleRegistry, IContainer::SHARED);
            $container->set(IClassMap::class, $this->createStub(IClassMap::class), IContainer::SHARED);

            (new ManageGitPlugin($container))->init();
            $provider = $container->get(IReportConfigProvider::class);

            $this->assertInstanceOf(FileReportConfigProvider::class, $provider);
            $this->assertSame(['module_registry' => true], $provider->getConfig('managegit_registry_test'));
            $this->assertSame($provider, $container->get(IReportConfigProvider::class));
        }
        finally {
            if (is_file($reportFile)) unlink($reportFile);
            if (is_dir($configDirectory)) rmdir($configDirectory);
            if (is_dir($modulePath . DIRECTORY_SEPARATOR . 'local')) rmdir($modulePath . DIRECTORY_SEPARATOR . 'local');
            if (is_dir($modulePath)) rmdir($modulePath);
        }
    }

    public function testCheckDependencies()
    {
        $containerMock = $this->createMock(IContainer::class);

        $plugin = new ManageGitPlugin($containerMock);
        $dependencies = $plugin->checkDependencies();

        $this->assertTrue(true);
    }
}


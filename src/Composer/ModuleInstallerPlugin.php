<?php

/**
 * This file is part of the package magicsunday/webtrees-module-installer-plugin.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Webtrees\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Throwable;

use function sprintf;

/**
 * Class ModuleInstallerPlugin.
 *
 * A Composer plugin to handle the installation, update, and uninstallation of webtrees modules.
 * This plugin listens to various Composer events and manages the deployment of packages of type
 * "webtrees-module" into a specific target directory within the webtrees application.
 */
class ModuleInstallerPlugin implements PluginInterface
{
    /**
     * @var Composer
     */
    private Composer $composer;

    /**
     * @var IOInterface
     */
    private IOInterface $io;

    /**
     * @var ModuleInstaller
     */
    private ModuleInstaller $installer;

    /**
     * @var array<string, InstallOperation|UpdateOperation|UninstallOperation>
     */
    private array $pendingPackages = [];

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer  = $composer;
        $this->io        = $io;
        $this->installer = new ModuleInstaller($io, $composer, ModuleInstaller::PACKAGE_TYPE);

        // Register the custom installer
        $composer
            ->getInstallationManager()
            ->addInstaller($this->installer);

        // Subscribe to package-level events
        $dispatcher = $composer->getEventDispatcher();

        $dispatcher->addListener(PackageEvents::PRE_PACKAGE_INSTALL, $this->onPrePackageEvent(...));
        $dispatcher->addListener(PackageEvents::PRE_PACKAGE_UPDATE, $this->onPrePackageEvent(...));
        $dispatcher->addListener(PackageEvents::PRE_PACKAGE_UNINSTALL, $this->onPrePackageEvent(...));

        $dispatcher->addListener(PackageEvents::POST_PACKAGE_INSTALL, $this->onPostPackageEvent(...));
        $dispatcher->addListener(PackageEvents::POST_PACKAGE_UPDATE, $this->onPostPackageEvent(...));
        $dispatcher->addListener(PackageEvents::POST_PACKAGE_UNINSTALL, $this->onPostPackageEvent(...));
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // nothing to do
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // nothing to do
    }

    public function onPrePackageEvent(PackageEvent $event): void
    {
        $this->installer->skipInstallation(false);

        /** @var InstallOperation|UpdateOperation|UninstallOperation $operation */
        $operation = $event->getOperation();

        if ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            $package = $operation->getPackage();
        }

        if ($package->getType() === ModuleInstaller::PACKAGE_TYPE) {
            $this->installer->skipInstallation(true);

            $this->pendingPackages[$package->getName()] = $operation;
        }

        // If root packages gets updated, reinstall all webtrees-module packages
        if (
            ($operation instanceof UpdateOperation)
            && ($package->getName() === ModuleInstaller::ROOT_PACKAGE_NAME)
        ) {
            $canonicalPackages = $this->composer
                ->getRepositoryManager()
                ->getLocalRepository()
                ->getCanonicalPackages();

            foreach ($canonicalPackages as $canonicalPackage) {
                if ($canonicalPackage->getType() !== ModuleInstaller::PACKAGE_TYPE) {
                    continue;
                }

                if (!isset($this->pendingPackages[$canonicalPackage->getName()])) {
                    $this->pendingPackages[$canonicalPackage->getName()] = new InstallOperation($canonicalPackage);
                }
            }
        }
    }

    public function onPostPackageEvent(PackageEvent $event): void
    {
        // Skip because there are no pending packages to install
        if ($this->pendingPackages === []) {
            return;
        }

        // Skip because the package "fisharebest/webtrees" is missing
        if ($this->installer->findWebtreesBasePath() === null) {
            return;
        }

        $this->io->write('<info>Installing Webtrees modules into the "modules_v4" directory</info>');

        $installManager = $this->composer
            ->getInstallationManager();

        foreach ($this->pendingPackages as $pendingPackageOperation) {
            $this->installer->skipInstallation(false);

            try {
                if ($pendingPackageOperation instanceof InstallOperation) {
                    $installManager->install(
                        $this->composer->getRepositoryManager()->getLocalRepository(),
                        $pendingPackageOperation
                    );
                }

                if ($pendingPackageOperation instanceof UpdateOperation) {
                    $installManager->update(
                        $this->composer->getRepositoryManager()->getLocalRepository(),
                        $pendingPackageOperation
                    );
                }

                if ($pendingPackageOperation instanceof UninstallOperation) {
                    $installManager->uninstall(
                        $this->composer->getRepositoryManager()->getLocalRepository(),
                        $pendingPackageOperation
                    );
                }
            } catch (Throwable $exception) {
                $this->io->write(
                    sprintf(
                        '<error>Failed to deploy module %s: %s</error>',
                        ($pendingPackageOperation instanceof UpdateOperation)
                            ? $pendingPackageOperation->getTargetPackage()->getName()
                            : $pendingPackageOperation->getPackage()->getName(),
                        $exception->getMessage()
                    )
                );
            }
        }

        $removals = 0;
        $updates  = 0;
        $installs = 0;

        foreach ($this->pendingPackages as $pendingPackageOperation) {
            switch ($pendingPackageOperation->getOperationType()) {
                case 'install':
                    ++$installs;
                    break;
                case 'update':
                    ++$updates;
                    break;
                case 'uninstall':
                    ++$removals;
                    break;
            }
        }

        $this->io->write(
            sprintf(
                '<info>Package operations: %d install%s, %d update%s, %d removal%s</info>',
                $installs,
                $installs === 1 ? '' : 's',
                $updates,
                $updates === 1 ? '' : 's',
                $removals,
                $removals === 1 ? '' : 's',
            )
        );

        $this->pendingPackages = [];
    }
}

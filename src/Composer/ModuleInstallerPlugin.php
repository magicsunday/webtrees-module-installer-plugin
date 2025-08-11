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
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use MagicSunday\Webtrees\Composer\Plugin\Config;

use function sprintf;

/**
 * Handles the integration of custom module installers into Composer.
 * Implements the PluginInterface to provide the required functionality
 * for activating, deactivating, and uninstalling the plugin.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/magicsunday/webtrees-module-installer-plugin/
 */
class ModuleInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * The list of modules that should be moved.
     *
     * @var array<int, array{path: string, package: PackageInterface}>
     */
    private array $pendingModules = [];

    /**
     * Activates the plugin by registering a custom installer with the Composer installation manager.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $installer = new ModuleInstaller($io, $composer, ModuleInstaller::PACKAGE_TYPE);
        $installer->setPluginConfig(Config::load($composer));

        $composer
            ->getInstallationManager()
            ->addInstaller($installer);
    }

    /**
     * Deactivates the plugin. This method is called during the deactivation process of the Composer plugin.
     *
     * @param Composer    $composer the Composer instance
     * @param IOInterface $io       the IO interface for output and input interactions
     *
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do
    }

    /**
     * Uninstalls the plugin. This method is called during the uninstallation process of the Composer plugin.
     *
     * @param Composer    $composer the Composer instance
     * @param IOInterface $io       the IO interface for output and input interactions
     *
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Nothing to do
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array<string, string> The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Called after the complete installation/update process
            ScriptEvents::POST_AUTOLOAD_DUMP => 'installModules',
        ];
    }

    /**
     * Adds a module to the pending modules list for later installation.
     *
     * @param PackageInterface $package     The package to be installed
     * @param string           $installPath The installation path
     *
     * @return void
     */
    public function addPendingModule(PackageInterface $package, string $installPath): void
    {
        $this->pendingModules[] = [
            'package' => $package,
            'path'    => $installPath,
        ];
    }

    /**
     * Installs modules only if the fisharebest/webtrees package is installed.
     *
     * @param Event $event
     *
     * @return void
     */
    public function installModules(Event $event): void
    {
        // Check if the package "fisharebest/webtrees" is installed
        $webtreesPackage = $event
            ->getComposer()
            ->getRepositoryManager()
            ->getLocalRepository()
            ->findPackage(
                Config::ROOT_PACKAGE_NAME,
                '*'
            );

        if ($webtreesPackage === null) {
            $event->getIO()->write(
                '<info>Skipping webtrees module installation as "fisharebest/webtrees" is not installed.</info>'
            );

            return;
        }

        if ($this->pendingModules === []) {
            $event->getIO()->write('<info>No webtrees modules to install/update.</info>');

            return;
        }

        $event->getIO()->write('<info>Installing webtrees modules...</info>');

        foreach ($this->pendingModules as $module) {
            $path        = $module['path'];
            $packageName = $module['package']->getPrettyName();

            // Handle promise if needed
            $event->getIO()->write(
                sprintf(
                    '  - Installing module <info>%s</info> (<comment>%s</comment>) to %s',
                    $module['package']->getPrettyName(),
                    $module['package']->getFullPrettyVersion(),
                    $path
                )
            );
        }

        // Reset the pending modules array after installation
        $this->pendingModules = [];
    }
}

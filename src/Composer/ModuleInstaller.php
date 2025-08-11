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
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use MagicSunday\Webtrees\Composer\Plugin\Config;
use React\Promise\PromiseInterface;

/**
 * Handles the installation of webtrees modules by managing the installation path,
 * verifying package types, and configuring plugin settings.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/magicsunday/webtrees-module-installer-plugin/
 */
class ModuleInstaller extends LibraryInstaller
{
    /**
     * The supported package type.
     */
    public const PACKAGE_TYPE = 'webtrees-module';

    /**
     * The directory used to install the module into.
     */
    private const MODULES_DIR = 'modules_v4' . DIRECTORY_SEPARATOR;

    /**
     * @var Config
     */
    private Config $pluginConfig;

    /**
     * Checks that provided package is installed.
     *
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     *
     * @return bool
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package): bool
    {
        // Always return "false" to reinstall the packages after installing/updating Webtrees.
        // For example, a downgrade may update only the "fisharebest/webtrees" package, while
        // the "webtrees-module" packages remain. However, since these packages must be installed
        // within the "fisharebest/webtrees" package, the "modules_v4" directory will otherwise be empty.
        return false;
    }

    /**
     * Retrieves the installation path for the given package.
     *
     * @param PackageInterface $package the package for which the installation path is determined
     *
     * @return string the computed installation path for the specified package
     */
    public function getInstallPath(PackageInterface $package): string
    {
        $separatorPos = strpos($package->getPrettyName(), DIRECTORY_SEPARATOR);
        $modulePath   = self::MODULES_DIR;

        if ($separatorPos !== false) {
            $modulePath .= substr($package->getPrettyName(), $separatorPos + 1);
        }

        return $this->getAppDirectory() . DIRECTORY_SEPARATOR . $modulePath;
    }

    /**
     * Installation step - store the path for later processing.
     *
     * @param InstalledRepositoryInterface $repo    The repository in which to check
     * @param PackageInterface             $package The package instance
     *
     * @return PromiseInterface<void|null>|null The installation path or a promise
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package): ?PromiseInterface
    {
        $promise = parent::install($repo, $package);

        /** @var Composer $composer */
        $composer = $this->composer;

        return $promise?->then(
            function () use ($composer, $package): void {
                $installPath = $this->getInstallPath($package);

                // Get the plugin instance to store the pending module
                $plugins = $composer->getPluginManager()->getPlugins();

                foreach ($plugins as $plugin) {
                    if ($plugin instanceof ModuleInstallerPlugin) {
                        $plugin->addPendingModule($package, $installPath);
                        break;
                    }
                }
            }
        );
    }

    /**
     * Retrieves the application directory path based on the plugin configuration.
     *
     * @return string the application directory path or an empty string if not configured
     */
    private function getAppDirectory(): string
    {
        return $this->pluginConfig->get('app-dir') ?? '';
    }

    /**
     * Sets the plugin configuration.
     *
     * @param Config $pluginConfig the configuration object for the plugin
     *
     * @return void
     */
    public function setPluginConfig(Config $pluginConfig): void
    {
        $this->pluginConfig = $pluginConfig;
    }
}

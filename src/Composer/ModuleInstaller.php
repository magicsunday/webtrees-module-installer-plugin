<?php

/**
 * This file is part of the package magicsunday/webtrees-module-installer-plugin.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Webtrees\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use MagicSunday\Webtrees\Composer\Plugin\Config;

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

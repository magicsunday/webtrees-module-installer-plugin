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
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use MagicSunday\Webtrees\Composer\Plugin\Config;

/**
 * Handles the integration of custom module installers into Composer.
 * Implements the PluginInterface to provide the required functionality
 * for activating, deactivating, and uninstalling the plugin.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/magicsunday/webtrees-module-installer-plugin/
 */
class ModuleInstallerPlugin implements PluginInterface
{
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
}

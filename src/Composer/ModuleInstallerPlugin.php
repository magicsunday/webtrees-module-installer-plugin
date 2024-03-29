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

/**
 * Composer module installer plugin.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/magicsunday/webtrees-module-installer-plugin/
 */
class ModuleInstallerPlugin implements PluginInterface
{
    /**
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $installer = new ModuleInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }

    /**
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }
}

<?php
/**
 * See LICENSE.md file for further details.
 */
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
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $installer = new ModuleInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }
}

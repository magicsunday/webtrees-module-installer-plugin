<?php

/**
 * See LICENSE.md file for further details.
 */

namespace MagicSunday\Webtrees\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

/**
 * Composer module installer.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/magicsunday/webtrees-module-installer-plugin/
 */
class ModuleInstaller extends LibraryInstaller
{
    public const PACKAGE_TYPE = 'webtrees-module';
    public const MODULES_DIR  = 'modules_v4/';

    public function getInstallPath(PackageInterface $package): string
    {
        $separator  = strpos($package->getPrettyName(), '/') + 1;
        $moduleName = substr($package->getPrettyName(), $separator);

        return self::MODULES_DIR . $moduleName;
    }

    public function supports($packageType): bool
    {
        return self::PACKAGE_TYPE === $packageType;
    }
}

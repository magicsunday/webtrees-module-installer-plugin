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

/**
 * Composer module installer.
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
    public const MODULES_DIR  = 'modules_v4/';

    /**
     * Returns the absolute installation path of a package.
     *
     * @param PackageInterface $package
     *
     * @return string
     */
    public function getInstallPath(PackageInterface $package): string
    {
        $separatorPos = strpos($package->getPrettyName(), '/');
        $modulePath   = self::MODULES_DIR;

        if ($separatorPos !== false) {
            /** @var string|false $moduleName */
            $moduleName = substr($package->getPrettyName(), $separatorPos + 1);

            if ($moduleName !== false) {
                $modulePath .= $moduleName;
            }
        }

        return $modulePath;
    }

    /**
     * Decides if the installer supports the given type.
     *
     * @param string $packageType
     *
     * @return bool
     */
    public function supports(string $packageType): bool
    {
        return self::PACKAGE_TYPE === $packageType;
    }
}

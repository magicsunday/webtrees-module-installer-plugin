<?php

/**
 * This file is part of the package magicsunday/webtrees-module-installer-plugin.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Webtrees\Composer;

use Composer\Config;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use React\Promise\PromiseInterface;
use ReflectionClass;

use function is_string;
use function React\Promise\resolve;

/**
 * ModuleInstaller is responsible for handling the installation of packages with the type `webtrees-module`.
 * It integrates with Composer's library installation process and ensures modules are correctly placed
 * into the appropriate path within the `fisharebest/webtrees` directory structure if installed.
 */
class ModuleInstaller extends LibraryInstaller
{
    public const ROOT_PACKAGE_NAME = 'fisharebest/webtrees';

    public const PACKAGE_TYPE = 'webtrees-module';

    /**
     * The directory used to install the module into.
     */
    private const MODULES_DIR = 'modules_v4' . DIRECTORY_SEPARATOR;

    private bool $skipInstall = false;

    public function getInstallPath(PackageInterface $package): string
    {
        $parts           = explode(DIRECTORY_SEPARATOR, $package->getPrettyName());
        $packageBaseName = end($parts);
        $webtreesPath    = $this->findWebtreesBasePath();

        if ($webtreesPath === null) {
            $vendorDirectory = $this->composer->getConfig()->get('vendor-dir');

            if (is_string($vendorDirectory)) {
                $webtreesPath = $vendorDirectory
                    . DIRECTORY_SEPARATOR . 'fisharebest'
                    . DIRECTORY_SEPARATOR . 'webtrees';
            }
        }

        return $webtreesPath
            . DIRECTORY_SEPARATOR . self::MODULES_DIR . $packageBaseName;
    }

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package): ?PromiseInterface
    {
        if ($this->skipInstall) {
            return resolve(null);
        }

        return parent::install($repo, $package);
    }

    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target): ?PromiseInterface
    {
        if ($this->skipInstall) {
            return resolve(null);
        }

        return parent::update($repo, $initial, $target);
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package): ?PromiseInterface
    {
        if ($this->skipInstall) {
            return resolve(null);
        }

        return parent::uninstall($repo, $package);
    }

    public function findWebtreesBasePath(): ?string
    {
        $webtreesPackage = $this->composer
            ->getRepositoryManager()
            ->getLocalRepository()
            ->findPackage(
                self::ROOT_PACKAGE_NAME,
                '*'
            );

        // Return installation path of webtrees package
        if ($webtreesPackage !== null) {
            $path = $this->composer
                ->getInstallationManager()
                ->getInstallPath($webtreesPackage);

            if (is_string($path) && is_dir($path)) {
                return rtrim(
                    $path,
                    DIRECTORY_SEPARATOR
                );
            }
        }

        // If webtrees exists as root package, return the base directory
        if ($this->composer->getPackage()->getName() === self::ROOT_PACKAGE_NAME) {
            $path = $this->getComposerBaseDir($this->composer->getConfig());

            if (is_string($path) && is_dir($path)) {
                return rtrim(
                    $path,
                    DIRECTORY_SEPARATOR
                );
            }
        }

        return null;
    }

    /**
     * @param Config $config
     *
     * @return string|null
     */
    private function getComposerBaseDir(Config $config): ?string
    {
        $reflectionClass    = new ReflectionClass($config);
        $reflectionProperty = $reflectionClass->getProperty('baseDir');

        $value = $reflectionProperty->getValue($config);

        if (is_string($value)) {
            return $value;
        }

        return null;
    }

    public function skipInstallation(bool $skip): void
    {
        $this->skipInstall = $skip;
    }
}

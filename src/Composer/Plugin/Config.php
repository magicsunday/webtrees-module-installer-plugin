<?php

/**
 * This file is part of the package magicsunday/webtrees-module-installer-plugin.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Webtrees\Composer\Plugin;

use Composer\Composer;
use ReflectionClass;

use function is_string;

/**
 * Handles application configuration values and paths. This class allows for
 * the management of dynamic configuration settings, including merging new
 * settings, resolving references in strings, and resolving absolute paths.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/magicsunday/webtrees-module-installer-plugin/
 */
class Config
{
    /**
     * The vendor directory where the packages should be installed.
     */
    private const VENDOR_DIRECTORY = DIRECTORY_SEPARATOR
        . 'vendor' . DIRECTORY_SEPARATOR . 'fisharebest' . DIRECTORY_SEPARATOR . 'webtrees';

    /**
     * The root package name.
     */
    public const ROOT_PACKAGE_NAME = 'fisharebest/webtrees';

    /**
     * @var array<string, string>
     */
    public static array $defaultConfig = [
        'web-dir' => 'public',
        'app-dir' => '{$base-dir}',
    ];

    /**
     * @var array<string, string>
     */
    private array $config;

    /**
     * @var string|null
     */
    private readonly ?string $baseDir;

    /**
     * Constructor.
     *
     * @param string|null $baseDir optional base directory
     */
    final public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir;
        $this->config  = static::$defaultConfig;
    }

    /**
     * @param Composer $composer
     *
     * @return Config
     */
    public static function load(Composer $composer): Config
    {
        /** @var Config|null $config */
        static $config;

        // Add the vendor directory to the base directory. This may be necessary, for example,
        // if the Webtrees package is orchestrated via a separate composer.json file and not installed
        // directly into the Webtrees package (fisharebest/webtrees).
        if ($composer->getPackage()->getPrettyName() !== self::ROOT_PACKAGE_NAME) {
            self::$defaultConfig['app-dir'] = '{$base-dir}' . self::VENDOR_DIRECTORY;
        }

        if ($config === null) {
            $baseDir = static::extractBaseDir($composer->getConfig());
            $config  = new static($baseDir);
        }

        return $config;
    }

    /**
     * Retrieves the value associated with the provided configuration key.
     *
     * @param string $key the configuration key to retrieve
     *
     * @return string|null the processed value associated with the key, or null if the key does not exist
     */
    public function get(string $key): ?string
    {
        switch ($key) {
            case 'web-dir':
            case 'app-dir':
                $val = rtrim($this->process($this->config[$key]), '/\\');

                return $this->realpath($val);

            case 'base-dir':
                return $this->baseDir !== null ? $this->realpath($this->baseDir) : '';

            default:
                if (!isset($this->config[$key])) {
                    return null;
                }

                return $this->process($this->config[$key]);
        }
    }

    /**
     * @param \Composer\Config $config
     *
     * @return string|null
     */
    protected static function extractBaseDir(\Composer\Config $config): ?string
    {
        $reflectionClass    = new ReflectionClass($config);
        $reflectionProperty = $reflectionClass->getProperty('baseDir');

        $value = $reflectionProperty->getValue($config);

        if (is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Processes the given string, replacing placeholders with corresponding values.
     *
     * @param string $value the string to process
     *
     * @return string the processed string with placeholders replaced
     */
    private function process(string $value): string
    {
        $config = $this;

        return preg_replace_callback(
            '#\{\$(.+)\}#',
            static fn (array $matches): string => $config->get($matches[1]) ?? '',
            $value
        ) ?? '';
    }

    /**
     * Turns relative paths into absolute paths without realpath().
     *
     * Since the dirs might not exist yet, we cannot call realpath, or it will fail.
     *
     * @param string $path
     *
     * @return string|null
     */
    private function realpath(string $path): ?string
    {
        if ($path === '') {
            return $this->baseDir;
        }

        if (($path[0] === DIRECTORY_SEPARATOR) || ($path[1] === ':')) {
            return $path;
        }

        return $this->baseDir . DIRECTORY_SEPARATOR . $path;
    }
}

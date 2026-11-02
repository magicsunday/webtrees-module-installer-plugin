<?php

/**
 * This file is part of the package magicsunday/webtrees-module-installer-plugin.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Webtrees\Test\EndToEnd;

use Composer\InstalledVersions;
use Composer\Util\Filesystem;
use JsonException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_diff;
use function array_filter;
use function bin2hex;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function is_dir;
use function is_string;
use function json_decode;
use function json_encode;
use function mkdir;
use function proc_close;
use function proc_open;
use function random_bytes;
use function scandir;
use function sort;
use function str_starts_with;
use function substr_count;
use function sys_get_temp_dir;

use const ARRAY_FILTER_USE_KEY;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_BINARY;

/**
 * Runs the plugin inside a real Composer process against a scratch project whose packages all come
 * from local path repositories, so no network is involved. The plugin writes into and removes from
 * the module directory of the user on every Composer run, which is why the behavior is checked on
 * the file system and not through stubs.
 *
 * Covers the module and the theme package type, once with webtrees installed as a dependency and
 * once with webtrees as the root package. The shipped example module that the webtrees package
 * carries next to the installed ones must survive every step.
 *
 * @phpstan-type Manifest array<string, string|array<array-key, string|bool|array<array-key, string|bool|array<string, bool>>>>
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/magicsunday/webtrees-module-installer-plugin/
 */
#[CoversNothing]
final class ModuleLifecycleTest extends TestCase
{
    /**
     * The Composer name of the fake module package.
     */
    private const string MODULE_NAME = 'acme/hello-module';

    /**
     * The directory name that the fake module package gets in the modules directory.
     */
    private const string MODULE_DIRECTORY = 'hello-module';

    /**
     * The directory name of the module that the webtrees package ships in its modules directory.
     */
    private const string EXAMPLE_DIRECTORY = 'example';

    /**
     * The scratch directory that holds the plugin copy, the fake packages, the project and the Composer home.
     */
    private string $workspace;

    /**
     * The Composer helper used to copy files and to clean the workspace up.
     */
    private Filesystem $filesystem;

    /**
     * Creates an empty scratch workspace and a copy of the plugin that carries only what a
     * distributed package carries, so the path repository does not mirror the build directory.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->workspace  = sys_get_temp_dir() . '/webtrees-installer-e2e-' . bin2hex(random_bytes(6));

        mkdir($this->workspace, 0o777, true);

        $pluginRoot = dirname(__DIR__, 2);

        // The copy has no version control metadata, so the scratch project gets the version from here.
        /** @var Manifest $pluginManifest */
        $pluginManifest = json_decode($this->readFile($pluginRoot . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        $pluginManifest['version'] = '99.0.0';

        $this->writeFiles($this->workspace . '/plugin', ['composer.json' => $this->encode($pluginManifest)]);
        $this->filesystem->copy($pluginRoot . '/src', $this->workspace . '/plugin/src');
    }

    /**
     * Removes the scratch workspace.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->filesystem->removeDirectory($this->workspace);
    }

    /**
     * Provides the combinations of package type and webtrees role.
     *
     * @return array<string, array{string, bool}>
     */
    public static function scenarioProvider(): array
    {
        return [
            'module, webtrees as dependency' => ['webtrees-module', false],
            'theme, webtrees as dependency'  => ['webtrees-theme', false],
            'module, webtrees as root'       => ['webtrees-module', true],
            'theme, webtrees as root'        => ['webtrees-theme', true],
        ];
    }

    /**
     * A module is installed into the modules directory of webtrees, an update replaces its content
     * without leaving a second copy or stale files, and a removal deletes the module directory while
     * the modules directory and the other modules in it stay.
     *
     * @param string $packageType    The Composer package type of the module
     * @param bool   $webtreesIsRoot Whether webtrees is the root package of the project
     *
     * @return void
     */
    #[Test]
    #[DataProvider('scenarioProvider')]
    public function installUpdateAndRemoveActOnTheModulesDirectory(string $packageType, bool $webtreesIsRoot): void
    {
        $project    = $this->workspace . '/project';
        $modulesDir = $webtreesIsRoot
            ? $project . '/modules_v4'
            : $project . '/vendor/fisharebest/webtrees/modules_v4';

        $moduleRequires = $webtreesIsRoot ? [] : ['fisharebest/webtrees' => '*'];

        $this->writeWebtreesPackage(['modules_v4/' . self::EXAMPLE_DIRECTORY . '/module.php' => '<?php // shipped example']);

        $this->writePackage(
            $this->workspace . '/modules/' . self::MODULE_DIRECTORY,
            [
                'name'    => self::MODULE_NAME,
                'type'    => $packageType,
                'version' => '1.0.0',
                'require' => $moduleRequires,
            ],
            [
                'module.php'   => 'first version',
                'obsolete.txt' => 'only in the first version',
            ]
        );

        $this->writeProject($project, $webtreesIsRoot, [self::MODULE_NAME => '*']);

        if ($webtreesIsRoot) {
            $this->writeFiles($project, ['modules_v4/' . self::EXAMPLE_DIRECTORY . '/module.php' => '<?php // shipped example']);
        }

        // Install
        $this->runComposer($project, 'install');

        self::assertSame(
            [self::EXAMPLE_DIRECTORY, self::MODULE_DIRECTORY],
            $this->listDirectory($modulesDir),
            'The install must place the module next to the shipped one.'
        );
        self::assertSame('first version', $this->readFile($modulesDir . '/' . self::MODULE_DIRECTORY . '/module.php'));
        self::assertDirectoryDoesNotExist(
            $project . '/vendor/acme',
            'The module must not additionally land in the default vendor location.'
        );

        // Update
        $this->writePackage(
            $this->workspace . '/modules/' . self::MODULE_DIRECTORY,
            [
                'name'    => self::MODULE_NAME,
                'type'    => $packageType,
                'version' => '1.1.0',
                'require' => $moduleRequires,
            ],
            [
                'module.php' => 'second version',
                'added.txt'  => 'only in the second version',
            ]
        );

        $this->runComposer($project, 'update');

        self::assertSame(
            [self::EXAMPLE_DIRECTORY, self::MODULE_DIRECTORY],
            $this->listDirectory($modulesDir),
            'The update must replace the module and leave no second copy.'
        );
        self::assertSame(
            ['added.txt', 'composer.json', 'module.php'],
            $this->listDirectory($modulesDir . '/' . self::MODULE_DIRECTORY),
            'The update must remove the files that the new version no longer has.'
        );
        self::assertSame('second version', $this->readFile($modulesDir . '/' . self::MODULE_DIRECTORY . '/module.php'));

        // Remove
        $this->writeProject($project, $webtreesIsRoot, []);
        $this->runComposer($project, 'update');

        self::assertSame(
            [self::EXAMPLE_DIRECTORY],
            $this->listDirectory($modulesDir),
            'The removal must delete only the module and keep the modules directory and the shipped module.'
        );
        self::assertSame('<?php // shipped example', $this->readFile($modulesDir . '/' . self::EXAMPLE_DIRECTORY . '/module.php'));
    }

    /**
     * Every queued operation runs exactly once, even when several modules are installed in one run
     * and Composer reports a package event after each of them.
     *
     * @return void
     */
    #[Test]
    public function installsEachQueuedModuleExactlyOnce(): void
    {
        $project = $this->workspace . '/project';

        $this->writeWebtreesPackage([]);

        foreach (['first-module', 'second-module'] as $directory) {
            $this->writePackage(
                $this->workspace . '/modules/' . $directory,
                [
                    'name'    => 'acme/' . $directory,
                    'type'    => 'webtrees-module',
                    'version' => '1.0.0',
                    'require' => ['fisharebest/webtrees' => '*'],
                ],
                ['module.php' => $directory]
            );
        }

        $this->writeProject($project, false, ['acme/first-module' => '*', 'acme/second-module' => '*']);

        $output = $this->runComposer($project, 'install');

        self::assertSame(1, substr_count($output, 'Installing acme/first-module'));
        self::assertSame(1, substr_count($output, 'Installing acme/second-module'));
        self::assertSame(
            ['first-module', 'second-module'],
            $this->listDirectory($project . '/vendor/fisharebest/webtrees/modules_v4')
        );
    }

    /**
     * Writes the fake webtrees package, which is installed as a dependency of the scratch project.
     *
     * @param array<string, string> $extraFiles Further files of the package by relative path
     *
     * @return void
     */
    private function writeWebtreesPackage(array $extraFiles): void
    {
        $this->writePackage(
            $this->workspace . '/webtrees',
            [
                'name'    => 'fisharebest/webtrees',
                'type'    => 'library',
                'version' => '2.2.0',
            ],
            ['app/Webtrees.php' => '<?php // webtrees'] + $extraFiles
        );
    }

    /**
     * Writes a package directory from scratch, so that files of an earlier version disappear.
     *
     * @param string                $directory The package directory
     * @param Manifest              $manifest  The content of the composer.json
     * @param array<string, string> $files     The files of the package by relative path
     *
     * @return void
     */
    private function writePackage(string $directory, array $manifest, array $files): void
    {
        $this->filesystem->removeDirectory($directory);

        $this->writeFiles($directory, ['composer.json' => $this->encode($manifest)] + $files);
    }

    /**
     * Writes the scratch project that requires the plugin and the given packages.
     *
     * @param string                $directory      The project directory
     * @param bool                  $webtreesIsRoot Whether the project is the webtrees root package
     * @param array<string, string> $modules        The module packages to require by name and constraint
     *
     * @return void
     */
    private function writeProject(string $directory, bool $webtreesIsRoot, array $modules): void
    {
        $repositories = [
            ['packagist.org' => false],
            ['type' => 'path', 'url' => $this->workspace . '/plugin', 'options' => ['symlink' => false]],
            ['type' => 'path', 'url' => $this->workspace . '/modules/*', 'options' => ['symlink' => false]],
        ];

        $require = ['magicsunday/webtrees-module-installer-plugin' => '*'] + $modules;

        if (!$webtreesIsRoot) {
            $repositories[]                  = ['type' => 'path', 'url' => $this->workspace . '/webtrees', 'options' => ['symlink' => false]];
            $require['fisharebest/webtrees'] = '*';
        }

        $this->writeFiles(
            $directory,
            [
                'composer.json' => $this->encode(
                    [
                        'name'              => $webtreesIsRoot ? 'fisharebest/webtrees' : 'acme/site',
                        'type'              => 'project',
                        'repositories'      => $repositories,
                        'require'           => $require,
                        'minimum-stability' => 'dev',
                        'config'            => [
                            'allow-plugins' => ['magicsunday/webtrees-module-installer-plugin' => true],
                            'lock'          => false,
                        ],
                    ]
                ),
            ]
        );
    }

    /**
     * Runs the Composer of the development dependencies in the given directory and fails with its
     * output when it does not succeed.
     *
     * @param string $workingDirectory The directory to run in
     * @param string $command          The Composer command to run
     *
     * @return string The standard output and the error output of the run
     *
     * @throws RuntimeException When the process cannot be started
     */
    private function runComposer(string $workingDirectory, string $command): string
    {
        $composerPath = InstalledVersions::getInstallPath('composer/composer');

        if ($composerPath === null) {
            throw new RuntimeException('Cannot find the installed Composer.');
        }

        $composerBin = $composerPath . '/bin/composer';
        $logFile     = $this->workspace . '/composer.log';
        $errorFile   = $this->workspace . '/composer-error.log';

        // The host's own Composer settings must not reach the scratch run.
        $environment = array_filter(
            getenv(),
            static fn (string $name): bool => !str_starts_with($name, 'COMPOSER'),
            ARRAY_FILTER_USE_KEY
        );

        $environment['COMPOSER_HOME']           = $this->workspace . '/composer-home';
        $environment['COMPOSER_CACHE_DIR']      = $this->workspace . '/composer-cache';
        $environment['COMPOSER_NO_INTERACTION'] = '1';
        $environment['COMPOSER_NO_AUDIT']       = '1';

        $process = proc_open(
            [PHP_BINARY, $composerBin, $command, '--no-interaction', '--no-ansi'],
            [
                1 => ['file', $logFile, 'w'],
                2 => ['file', $errorFile, 'w'],
            ],
            $pipes,
            $workingDirectory,
            $environment
        );

        if ($process === false) {
            throw new RuntimeException('Cannot start Composer.');
        }

        $exitCode = proc_close($process);

        self::assertSame(
            0,
            $exitCode,
            'Composer ' . $command . ' failed: ' . $this->readFile($logFile) . $this->readFile($errorFile)
        );

        return $this->readFile($logFile) . $this->readFile($errorFile);
    }

    /**
     * Writes files below a directory and creates the directories on the way.
     *
     * @param string                $directory The base directory
     * @param array<string, string> $files     The file contents by relative path
     *
     * @return void
     */
    private function writeFiles(string $directory, array $files): void
    {
        foreach ($files as $relativePath => $content) {
            $path = $directory . '/' . $relativePath;

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0o777, true);
            }

            file_put_contents($path, $content);
        }
    }

    /**
     * Encodes a Composer manifest.
     *
     * @param Manifest $manifest The manifest content
     *
     * @return string
     *
     * @throws JsonException When the manifest cannot be encoded
     */
    private function encode(array $manifest): string
    {
        return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Reads a file that must exist.
     *
     * @param string $path The file path
     *
     * @return string
     *
     * @throws RuntimeException When the file cannot be read
     */
    private function readFile(string $path): string
    {
        $content = file_get_contents($path);

        if (!is_string($content)) {
            throw new RuntimeException('Cannot read ' . $path);
        }

        return $content;
    }

    /**
     * Lists the entries of a directory by name, sorted, without the dot entries.
     *
     * @param string $directory The directory to list
     *
     * @return list<string>
     *
     * @throws RuntimeException When the directory cannot be listed
     */
    private function listDirectory(string $directory): array
    {
        $entries = scandir($directory);

        if ($entries === false) {
            throw new RuntimeException('Cannot list ' . $directory);
        }

        $names = array_diff($entries, ['.', '..']);

        sort($names);

        return $names;
    }
}

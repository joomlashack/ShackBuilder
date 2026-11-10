<?php

/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2026 Joomlashack.com. All rights reserved
 * @license   https://www.gnu.org/licenses/gpl.html GNU/GPL
 *
 * This file is part of ShackBuilder.
 *
 * ShackBuilder is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * ShackBuilder is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ShackBuilder.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace ShackBuilder\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use ShackBuilder\Tests\Support\PhingTestCase;

/**
 * phing build for free extensions of each type
 */
class BuildTest extends PhingTestCase
{
    public function testBuildCreatesPackage()
    {
        $path = $this->createExtension('Foo', [
            'files' => [
                'src/foo.php'                                     => '<?php // foo',
                'src/language/en-GB/en-GB.plg_system_foo.ini'     => 'PLG_SYSTEM_FOO="Foo"',
                'src/language/en-GB/en-GB.plg_system_foo.sys.ini' => 'PLG_SYSTEM_FOO="Foo"',
            ],
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $package = $path . '/packages/plg_system_foo_free_1.2.3.zip';
        $this->assertSame(
            [
                'foo.php',
                'foo.xml',
                'language/en-GB/en-GB.plg_system_foo.ini',
                'language/en-GB/en-GB.plg_system_foo.sys.ini',
            ],
            $this->zipEntries($package)
        );
        $this->assertSame('<?php // foo', $this->zipContent($package, 'foo.php'));
        $this->assertDirectoryDoesNotExist($path . '/packages/tmp');
    }

    public function testBuildIsTheDefaultTarget()
    {
        $path = $this->createExtension('Foo', ['files' => ['src/foo.php' => '<?php']]);

        $result = $this->runPhing($path);

        $this->assertBuildSucceeded($result);
        $this->assertFileExists($path . '/packages/plg_system_foo_free_1.2.3.zip');
    }

    public function testBuildDoesNotChangeVersion()
    {
        $path   = $this->createExtension('Foo');
        $before = file_get_contents($path . '/src/foo.xml');

        $this->assertBuildSucceeded($this->runPhing($path, ['build']));

        $this->assertSame($before, file_get_contents($path . '/src/foo.xml'));
    }

    public function testBuildStartsFromAnEmptyFolder()
    {
        $path = $this->createExtension('Foo', ['files' => ['src/foo.php' => '<?php']]);
        $this->writeFile($path . '/packages/tmp/stale.php', '<?php // left over');
        $this->writeFile($path . '/packages/plg_system_foo_free_1.2.3.zip', 'not a zip');

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertSame(
            ['foo.php', 'foo.xml'],
            $this->zipEntries($path . '/packages/plg_system_foo_free_1.2.3.zip')
        );
    }

    public function testDebugKeepsTemporaryFiles()
    {
        $path = $this->createExtension('Foo', ['files' => ['src/foo.php' => '<?php']]);

        $result = $this->runPhing($path, ['build', '-Dbuilder.debug=1']);

        $this->assertBuildSucceeded($result);
        $this->assertFileExists($path . '/packages/tmp/foo.php');
        $this->assertFileExists($path . '/packages/plg_system_foo_free_1.2.3.zip');
        $this->assertStringContainsString(
            'composer.extra.element=plg_system_foo',
            $this->readFile($path . '/package.properties')
        );
    }

    public function testPackageCanBeMoved()
    {
        $path = $this->createExtension('Foo', [
            'properties' => [
                'project.movePackage'   => '1',
                'project.movePackageTo' => $this->tmpPath . '/dist',
            ],
        ]);
        mkdir($this->tmpPath . '/dist');

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertFileExists($this->tmpPath . '/dist/plg_system_foo_free_1.2.3.zip');
        $this->assertFileDoesNotExist($path . '/packages/plg_system_foo_free_1.2.3.zip');
    }

    public static function extensionTypeProvider(): array
    {
        return [
            'component' => [
                [
                    'type'    => 'component',
                    'element' => 'com_foo',
                    'files'   => [
                        'src/admin/foo.php' => '<?php',
                        'src/site/foo.php'  => '<?php',
                    ],
                ],
                'com_foo_free_1.2.3.zip',
                ['admin/foo.php', 'foo.xml', 'site/foo.php'],
            ],
            'module' => [
                [
                    'type'         => 'module',
                    'element'      => 'mod_foo',
                    'manifestName' => 'mod_foo.xml',
                    'files'        => ['src/mod_foo.php' => '<?php'],
                ],
                'mod_foo_free_1.2.3.zip',
                ['mod_foo.php', 'mod_foo.xml'],
            ],
            'library' => [
                [
                    'type'    => 'library',
                    'element' => 'lib_foo',
                    'files'   => ['src/library.php' => '<?php'],
                ],
                'lib_foo_free_1.2.3.zip',
                ['foo.xml', 'library.php'],
            ],
            'file' => [
                [
                    'type'    => 'file',
                    'element' => 'file_foo',
                    'files'   => ['src/cli/foo.php' => '<?php'],
                ],
                'file_foo_free_1.2.3.zip',
                ['cli/foo.php', 'foo.xml'],
            ],
            'package' => [
                [
                    'type'    => 'package',
                    'element' => 'pkg_foo',
                    'files'   => ['src/packages/plg_system_foo.zip' => 'zip'],
                ],
                'pkg_foo_free_1.2.3.zip',
                ['foo.xml', 'packages/plg_system_foo.zip'],
            ],
        ];
    }

    #[DataProvider('extensionTypeProvider')]
    public function testExtensionTypes(array $options, string $packageName, array $entries)
    {
        $path = $this->createExtension('Foo', $options);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertSame($entries, $this->zipEntries($path . '/packages/' . $packageName));
    }

    public function testManifestNamedWithTypePrefix()
    {
        // When <short>.xml is missing, <type>_<short>.xml is used
        $path = $this->createExtension('Foo', [
            'type'         => 'module',
            'element'      => 'mod_foo',
            'manifestName' => 'mod_foo.xml',
            'version'      => '4.5.6',
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertFileExists($path . '/packages/mod_foo_free_4.5.6.zip');
    }

    public function testListTargets()
    {
        $path = $this->createExtension('Foo');

        $result = $this->runPhing($path, ['-l']);

        $this->assertSame(0, $result->exitCode, $result->output);

        $targets = [
            'build',
            'build-new',
            'copy-language',
            'merge-languages',
            'pre-build',
            'set-version',
            'standards',
            'update-creation',
            'version',
        ];
        foreach ($targets as $target) {
            $this->assertMatchesRegularExpression('/^ ' . preg_quote($target) . '\s/m', $result->output);
        }
        $this->assertDoesNotMatchRegularExpression('/^ release\s/m', $result->output);
    }
}

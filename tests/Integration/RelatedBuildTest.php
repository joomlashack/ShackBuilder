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
 * Things pulled into a package from other repos: related extensions, includes and the installer
 */
class RelatedBuildTest extends PhingTestCase
{
    protected const RELATED = <<<XML
        <relatedExtensions>
            <extension type="plugin" group="system" element="bar">plg_system_bar</extension>
            <extension type="library" element="baz">lib_baz</extension>
        </relatedExtensions>
XML;

    public static function parallelProvider(): array
    {
        return [
            'parallel' => [true],
            'serial'   => [false],
        ];
    }

    #[DataProvider('parallelProvider')]
    public function testRelatedExtensionsAreBuiltFromTheirRepos(bool $parallel)
    {
        $this->createExtension('plg_system_bar', [
            'element' => 'plg_system_bar',
            'short'   => 'bar',
            'name'    => 'Bar',
            'files'   => ['src/bar.php' => '<?php // bar'],
        ]);
        $this->createExtension('lib_baz', [
            'type'    => 'library',
            'element' => 'lib_baz',
            'short'   => 'baz',
            'name'    => 'Baz',
            'files'   => ['src/baz.php' => '<?php // baz'],
        ]);
        $path = $this->createComponent([
            'project.plg_system_bar.path' => $this->tmpPath . '/plg_system_bar',
            'project.lib_baz.path'        => $this->tmpPath . '/lib_baz',
        ]);

        $result = $this->runPhing($path, ['build', '-Dcapabilities.parallel=' . ($parallel ? '1' : '0')]);

        $this->assertBuildSucceeded($result);
        $package = $path . '/packages/com_foo_free_1.2.3.zip';
        $this->assertSame(
            [
                'admin/foo.php',
                'extensions/lib_baz/baz.php',
                'extensions/lib_baz/baz.xml',
                'extensions/plg_system_bar/bar.php',
                'extensions/plg_system_bar/bar.xml',
                'foo.xml',
            ],
            $this->zipEntries($package)
        );
        $this->assertSame('<?php // bar', $this->zipContent($package, 'extensions/plg_system_bar/bar.php'));

        // Related extensions are not zipped on their own
        $this->assertDirectoryDoesNotExist($this->tmpPath . '/plg_system_bar/packages');
    }

    public function testEmbeddedRelatedExtensionsAreNotRebuilt()
    {
        $path = $this->createComponent([], [
            'src/extensions/plg_system_bar/bar.php' => '<?php // embedded bar',
            'src/extensions/plg_system_bar/bar.xml' => '<extension/>',
            'src/extensions/lib_baz/baz.php'        => '<?php // embedded baz',
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertStringContainsString('The extension plg_system_bar is pre-packed', $result->output);
        $this->assertSame(
            '<?php // embedded bar',
            $this->zipContent($path . '/packages/com_foo_free_1.2.3.zip', 'extensions/plg_system_bar/bar.php')
        );
    }

    #[DataProvider('parallelProvider')]
    public function testMissingRelatedExtensionPathFails(bool $parallel)
    {
        $path = $this->createComponent();

        $result = $this->runPhing($path, ['build', '-Dcapabilities.parallel=' . ($parallel ? '1' : '0')]);

        $this->assertBuildFailed($result, 'Related extension path property is missing: project.plg_system_bar.path');
        $this->assertFileDoesNotExist($path . '/packages/com_foo_free_1.2.3.zip');
    }

    public function testRelatedExtensionsInsideRelatedExtensionsAreNotBuiltTwice()
    {
        // lib_baz is related to both com_foo and plg_system_bar
        $this->createExtension('plg_system_bar', [
            'element'    => 'plg_system_bar',
            'short'      => 'bar',
            'name'       => 'Bar',
            'alledia'    => '<relatedExtensions><extension type="library" element="baz">lib_baz</extension></relatedExtensions>',
            'files'      => ['src/bar.php' => '<?php'],
            'properties' => ['project.lib_baz.path' => $this->tmpPath . '/lib_baz'],
        ]);
        $this->createExtension('lib_baz', [
            'type'    => 'library',
            'element' => 'lib_baz',
            'short'   => 'baz',
            'name'    => 'Baz',
            'files'   => ['src/baz.php' => '<?php'],
        ]);
        $path = $this->createComponent([
            'project.plg_system_bar.path' => $this->tmpPath . '/plg_system_bar',
            'project.lib_baz.path'        => $this->tmpPath . '/lib_baz',
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertStringContainsString('Ignored some related extensions: plg_system_bar,lib_baz', $result->output);
        $this->assertNotContains(
            'extensions/plg_system_bar/extensions/lib_baz/baz.php',
            $this->zipEntries($path . '/packages/com_foo_free_1.2.3.zip')
        );
    }

    public function testIncludesAreCopied()
    {
        $this->createInclude('Shared', [
            'src/shared.php'       => '<?php // shared',
            'src/admin/helper.php' => '<?php // admin helper',
        ]);
        $this->createInclude('Library', ['library/lib.php' => '<?php // lib']);

        $path = $this->createExtension('Foo', [
            'alledia'    => <<<XML
        <include>Shared</include>
        <include source="library/" destination="vendor/lib">Library</include>
XML,
            'files'      => ['src/foo.php' => '<?php'],
            'properties' => [
                'project.Shared.path'  => $this->tmpPath . '/Shared',
                'project.Library.path' => $this->tmpPath . '/Library',
            ],
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $package = $path . '/packages/plg_system_foo_free_1.2.3.zip';
        $this->assertSame(
            [
                'foo.php',
                'foo.xml',
                // admin/ is flattened for anything but components
                'helper.php',
                'shared.php',
                'vendor/lib/lib.php',
            ],
            $this->zipEntries($package)
        );
        $this->assertSame('<?php // shared', $this->zipContent($package, 'shared.php'));
    }

    public function testIncludesKeepAdminFolderForComponents()
    {
        $this->createInclude('Shared', ['src/admin/helper.php' => '<?php']);
        $path = $this->createExtension('Foo', [
            'type'       => 'component',
            'element'    => 'com_foo',
            'alledia'    => '<include>Shared</include>',
            'properties' => ['project.Shared.path' => $this->tmpPath . '/Shared'],
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertSame(
            ['admin/helper.php', 'foo.xml'],
            $this->zipEntries($path . '/packages/com_foo_free_1.2.3.zip')
        );
    }

    public function testMissingIncludePathFails()
    {
        $path = $this->createExtension('Foo', ['alledia' => '<include>Shared</include>']);

        $result = $this->runPhing($path, ['build', '-Dcapabilities.parallel=0']);

        $this->assertBuildFailed($result, 'Missing include property: project.Shared.path');
    }

    public function testIncludeRepoCanBeBuiltDirectly()
    {
        $path = $this->createInclude('Shared', [
            'src/shared.php'       => '<?php',
            'src/admin/helper.php' => '<?php',
        ]);

        $result = $this->runPhing($path);

        $this->assertBuildSucceeded($result);
        $this->assertFileExists($path . '/packages/tmp/shared.php');
        // No project.type when run directly, so admin/ is flattened
        $this->assertFileExists($path . '/packages/tmp/helper.php');
    }

    #[DataProvider('installerProvider')]
    public function testInstallerIsCopiedForScriptfile(array $options, array $entries)
    {
        $installer = $this->tmpPath . '/ShackInstaller';
        $this->writeFile($installer . '/src/include.php', '<?php // installer');
        $this->writeFile($installer . '/src/media/installer.css', 'body {}');

        $path = $this->createExtension('Foo', array_merge(
            $options,
            [
                'manifestExtra' => '    <scriptfile>script.installer.php</scriptfile>',
                'files'         => ['src/script.installer.php' => '<?php'],
                'properties'    => ['project.ShackInstaller.path' => $installer],
            ]
        ));

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertSame($entries, $this->zipEntries($path . '/packages/' . $options['element'] . '_free_1.2.3.zip'));
    }

    public static function installerProvider(): array
    {
        return [
            'plugin'    => [
                ['element' => 'plg_system_foo'],
                ['foo.xml', 'include.php', 'media/installer.css', 'script.installer.php'],
            ],
            'component' => [
                ['type' => 'component', 'element' => 'com_foo'],
                ['admin/include.php', 'foo.xml', 'media/installer.css', 'script.installer.php'],
            ],
        ];
    }

    public function testScriptfileNeedsInstallerPath()
    {
        $path = $this->createExtension('Foo', [
            'manifestExtra' => '    <scriptfile>script.installer.php</scriptfile>',
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildFailed($result, 'Missing Installer property: project.ShackInstaller.path');
    }

    /**
     * @param array $properties
     * @param array $files
     *
     * @return string
     */
    protected function createComponent(array $properties = [], array $files = []): string
    {
        return $this->createExtension('Foo', [
            'type'       => 'component',
            'element'    => 'com_foo',
            'alledia'    => static::RELATED,
            'files'      => array_merge(['src/admin/foo.php' => '<?php'], $files),
            'properties' => $properties,
        ]);
    }

    /**
     * Create an include-only repo
     *
     * @param string $folder
     * @param array  $files
     *
     * @return string
     */
    protected function createInclude(string $folder, array $files): string
    {
        $path = $this->tmpPath . '/' . $folder;

        // Include repos use the client build.xml with these two changes
        $buildFile = str_replace(
            ['default="build"', '<property name="builder.type" value="build"/>'],
            ['default="copy"', '<property name="builder.type" value="build-include"/>'],
            file_get_contents(static::BUILDER_ROOT . '/src/clients/build.xml')
        );
        $this->writeFile($path . '/build.xml', $buildFile);
        $this->writeFile($path . '/build.properties', $this->formatProperties(['builder.path' => $this->builderPath]));

        foreach ($files as $file => $content) {
            $this->writeFile($path . '/' . $file, $content);
        }

        return $path;
    }
}

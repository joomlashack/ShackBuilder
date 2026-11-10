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
 * version, set-version, build-new and update-creation
 */
class VersionTest extends PhingTestCase
{
    /**
     * Fixes "today" for the creation date
     */
    protected const TODAY_ARG = '-Dphing.tstamp.now.iso=2026-03-05T12:00:00';

    protected const TODAY = 'March 5, 2026';

    public function testVersion()
    {
        $path = $this->createExtension('Foo');

        $result = $this->runPhing($path, ['version']);

        $this->assertBuildSucceeded($result);
        $this->assertStringContainsString('Current version: 1.2.3', $result->output);
        $this->assertStringContainsString('Creation date: January 1, 2020', $result->output);
    }

    public static function releaseProvider(): array
    {
        return [
            'major'                 => ["major\n", '2.0.0'],
            'minor'                 => ["minor\n", '1.3.0'],
            'bugfix'                => ["bugfix\n", '1.2.4'],
            'alpha, same base'      => ["alpha\nnone\n", '1.2.3a1'],
            'alpha, new minor'      => ["alpha\nminor\n", '1.3.0a1'],
            'beta, new major'       => ["beta\nmajor\n", '2.0.0b1'],
            'release candidate'     => ["rc\nbugfix\n", '1.2.4rc1'],
            'custom'                => ["custom\n9.8.7\n", '9.8.7'],
            'stable'                => ["stable\n", '1.2.3'],
        ];
    }

    #[DataProvider('releaseProvider')]
    public function testSetVersion(string $answers, string $expected)
    {
        $path = $this->createExtension('Foo');

        $result = $this->runPhing($path, ['set-version', static::TODAY_ARG], $answers);

        $this->assertBuildSucceeded($result);
        $this->assertStringContainsString('Current version: ' . $expected, $result->output);

        $manifest = simplexml_load_file($path . '/src/foo.xml');
        $this->assertSame($expected, (string)$manifest->version);
        $this->assertSame(static::TODAY, (string)$manifest->creationDate);

        $this->assertDirectoryDoesNotExist($path . '/packages');
    }

    public function testPreReleaseOfPreRelease()
    {
        $path = $this->createExtension('Foo', ['version' => '1.2.3b1']);

        $result = $this->runPhing($path, ['set-version', static::TODAY_ARG], "beta\nnone\n");

        $this->assertBuildSucceeded($result);
        $this->assertSame('1.2.3b2', (string)simplexml_load_file($path . '/src/foo.xml')->version);
    }

    public function testBuildNew()
    {
        $path = $this->createExtension('Foo', ['files' => ['src/foo.php' => '<?php']]);

        $result = $this->runPhing($path, ['build-new', static::TODAY_ARG], "minor\n");

        $this->assertBuildSucceeded($result);

        $package = $path . '/packages/plg_system_foo_free_1.3.0.zip';
        $this->assertSame(['foo.php', 'foo.xml'], $this->zipEntries($package));

        $manifest = simplexml_load_string($this->zipContent($package, 'foo.xml'));
        $this->assertSame('1.3.0', (string)$manifest->version);
        $this->assertSame(static::TODAY, (string)$manifest->creationDate);

        $this->assertSame('1.3.0', (string)simplexml_load_file($path . '/src/foo.xml')->version);
    }

    public function testEmbeddedRelatedExtensionsGetTheNewVersion()
    {
        $related = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="%s" method="upgrade">
    <name>%s</name>
    <version>0.0.1</version>
    <creationDate>January 1, 2019</creationDate>
</extension>

XML;
        $path = $this->createExtension('Foo', [
            'type'    => 'component',
            'element' => 'com_foo',
            'alledia' => <<<XML
        <relatedExtensions>
            <extension type="plugin" group="content" element="foo">plg_content_foo</extension>
            <extension type="module" element="foofiles">mod_foofiles</extension>
        </relatedExtensions>
XML,
            'files'   => [
                'src/extensions/plg_content_foo/foo.xml'       => sprintf($related, 'plugin', 'plg_content_foo'),
                'src/extensions/mod_foofiles/mod_foofiles.xml' => sprintf($related, 'module', 'mod_foofiles'),
            ],
        ]);

        $result = $this->runPhing($path, ['set-version', static::TODAY_ARG], "bugfix\n");

        $this->assertBuildSucceeded($result);
        foreach (['plg_content_foo/foo.xml', 'mod_foofiles/mod_foofiles.xml'] as $file) {
            $manifest = simplexml_load_file($path . '/src/extensions/' . $file);
            $this->assertSame('1.2.4', (string)$manifest->version, $file);
            $this->assertSame(static::TODAY, (string)$manifest->creationDate, $file);
        }
    }

    public function testUpdateCreation()
    {
        $path = $this->createExtension('Foo');

        $result = $this->runPhing($path, ['update-creation', static::TODAY_ARG]);

        $this->assertBuildSucceeded($result);
        $this->assertStringContainsString('Creation Date: ' . static::TODAY, $result->output);

        $manifest = simplexml_load_file($path . '/src/foo.xml');
        $this->assertSame(static::TODAY, (string)$manifest->creationDate);
        $this->assertSame('1.2.3', (string)$manifest->version);
    }
}

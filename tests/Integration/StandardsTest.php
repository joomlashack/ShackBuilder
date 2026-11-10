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

class StandardsTest extends PhingTestCase
{
    public static function phpProvider(): array
    {
        return [
            'default'        => [[], '>=8.2'],
            'version'        => [['-Dphp=8.3'], '>=8.3'],
            'constraint'     => [['-Dphp=^8.4'], '^8.4'],
            'empty keeps it' => [['-Dphp='], '>=8.1'],
        ];
    }

    #[DataProvider('phpProvider')]
    public function testPhpVersion(array $args, string $expected)
    {
        $path = $this->createExtension('Foo');

        $result = $this->runPhing($path, array_merge(['standards'], $args));

        $this->assertBuildSucceeded($result);
        $composer = json_decode($this->readFile($path . '/composer.json'), true);
        $this->assertSame($expected, $composer['require']['php']);
    }

    public function testComposerAndManifestAreStandardized()
    {
        $path = $this->createExtension('Foo', [
            'composer' => [
                'name'    => 'Joomlashack/Foo',
                'license' => 'GPL-2+',
                'extra'   => ['target-platform' => '3.*'],
            ],
        ]);
        $manifest = $path . '/src/foo.xml';
        file_put_contents(
            $manifest,
            str_replace(
                ['<extension type', '<author>Joomlashack</author>'],
                ['<extension version="3.9" type', '<author>Somebody</author>'],
                file_get_contents($manifest)
            )
        );

        $result = $this->runPhing($path, ['standards']);

        $this->assertBuildSucceeded($result);
        $this->assertStringContainsString('composer.json has been updated', $result->output);
        $this->assertStringContainsString('src/foo.xml manifest has been updated', $result->output);

        $composer = json_decode($this->readFile($path . '/composer.json'), true);
        $this->assertSame('joomlashack/foo', $composer['name']);
        $this->assertSame('GPL-2.0-or-later', $composer['license']);
        $this->assertSame('.*', $composer['extra']['target-platform']);

        $manifestText = $this->readFile($manifest);
        $this->assertStringNotContainsString('version="3.9"', $manifestText);
        $this->assertStringContainsString('<author>Joomlashack</author>', $manifestText);
    }

    public function testDebugWritesProperties()
    {
        $path = $this->createExtension('Foo');

        $result = $this->runPhing($path, ['standards', '-Dbuilder.debug=1']);

        $this->assertBuildSucceeded($result);
        $this->assertStringContainsString('project.name.short=foo', $this->readFile($path . '/standards.properties'));
    }

    public function testEmbeddedModuleWithPrefixedElement()
    {
        // findManifestFile() is meant to accept a module element with or without mod_
        $path = $this->createExtension('Foo', [
            'type'    => 'component',
            'element' => 'com_foo',
            'alledia' => <<<XML
        <relatedExtensions>
            <extension type="module" element="mod_foofiles">mod_foofiles</extension>
        </relatedExtensions>
XML,
            'files'   => [
                'src/extensions/mod_foofiles/mod_foofiles.xml' => <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="module" method="upgrade">
    <name>mod_foofiles</name>
    <author>Somebody</author>
    <version>1.0.0</version>
    <creationDate>January 1, 2020</creationDate>
</extension>

XML,
            ],
        ]);

        $result = $this->runPhing($path, ['standards']);

        $this->assertBuildSucceeded($result);
        $this->assertStringContainsString(
            '<author>Joomlashack</author>',
            $this->readFile($path . '/src/extensions/mod_foofiles/mod_foofiles.xml')
        );
    }
}

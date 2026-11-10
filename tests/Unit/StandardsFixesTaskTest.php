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

namespace ShackBuilder\Tests\Unit;

use Phing\Project;
use PHPUnit\Framework\Attributes\DataProvider;
use ShackBuilder\Tasks\StandardsFixesTask;
use ShackBuilder\Tests\Support\TaskTestCase;

class StandardsFixesTaskTest extends TaskTestCase
{
    protected const COMPOSER = <<<JSON
{
    "name"   : "Joomlashack/Foo",
    "license": "GPL-2+",
    "type"   : "joomla.plugin",
    "extra"  : {
        "target-platform": "3.*"
    },
    "require": {
        "php": ">=7.4"
    }
}

JSON;

    protected const STANDARD_COMPOSER = <<<JSON
{
    "name"   : "joomlashack/foo",
    "license": "GPL-2.0-or-later",
    "type"   : "joomla-plugin",
    "extra"  : {
        "target-platform": ".*"
    },
    "require": {
        "php": ">=8.2"
    }
}

JSON;

    protected const STANDARD_MANIFEST = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="system" method="upgrade">
    <name>plg_system_foo</name>
    <author>Joomlashack</author>
    <authorEmail>help@joomlashack.com</authorEmail>
    <authorUrl>https://www.joomlashack.com/</authorUrl>
    <copyright>Copyright 2026 Joomlashack.com</copyright>
    <license>GNU GPL; see LICENSE file</license>
    <description>PLG_SYSTEM_FOO_DESCRIPTION</description>
    <version>1.2.3</version>
    <creationDate>January 1, 2020</creationDate>

    <files>
        <filename plugin="foo">foo.php</filename>
    </files>
</extension>

XML;

    public function testComposerIsStandardized()
    {
        $this->writeFile('composer.json', static::COMPOSER);
        $this->writeFile('src/foo.xml', static::STANDARD_MANIFEST);

        $this->runTask('8.2');

        $this->assertSame(static::STANDARD_COMPOSER, $this->readFile('composer.json'));
        $this->assertStringContainsString(
            'composer.json has been updated',
            $this->logs->getText(Project::MSG_WARN)
        );
    }

    public function testStandardFilesAreNotChanged()
    {
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile('src/foo.xml', static::STANDARD_MANIFEST);

        $this->runTask('8.2');

        $this->assertSame(static::STANDARD_COMPOSER, $this->readFile('composer.json'));
        $this->assertSame(static::STANDARD_MANIFEST, $this->readFile('src/foo.xml'));
        $this->assertSame([], $this->logs->getMessages(Project::MSG_WARN));
    }

    public function testPhpVersionIsLeftAloneWhenNotGiven()
    {
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile('src/foo.xml', static::STANDARD_MANIFEST);

        $this->runTask(null);

        $this->assertStringContainsString('"php": ">=8.2"', $this->readFile('composer.json'));
    }

    public static function phpProvider(): array
    {
        return [
            'plain version'      => ['8.3', '>=8.3'],
            'with operator'      => ['>=8.1', '>=8.1'],
            'caret constraint'   => ['^8.4', '^8.4'],
            'surrounding spaces' => [' 8.3 ', '>=8.3'],
            'empty keeps it'     => ['', '>=8.2'],
            'blank keeps it'     => ['  ', '>=8.2'],
        ];
    }

    #[DataProvider('phpProvider')]
    public function testPhpVersion(string $php, string $expected)
    {
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile('src/foo.xml', static::STANDARD_MANIFEST);

        $this->runTask($php);

        $this->assertStringContainsString(
            sprintf('"php": "%s"', $expected),
            $this->readFile('composer.json')
        );
    }

    public function testManifestIsStandardized()
    {
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile(
            'src/foo.xml',
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension version="3.9" type="plugin" group="system" method="upgrade">
    <version>1.2.3</version>
    <name>plg_system_foo</name>
    <creationDate>January 1, 2020</creationDate>
    <author>Somebody Else</author>
    <authorEmail>someone@example.com</authorEmail>
    <authorUrl>http://example.com</authorUrl>
    <packager>Joomlashack</packager>
    <packagerurl>https://www.joomlashack.com</packagerurl>
    <license>GPL</license>
    <copyright>Copyright 2026 Joomlashack.com</copyright>
    <description>PLG_SYSTEM_FOO_DESCRIPTION</description>

    <files>
        <filename plugin="foo">foo.php</filename>
    </files>
</extension>

XML
        );

        $this->runTask('8.2');

        $this->assertSame(static::STANDARD_MANIFEST, $this->readFile('src/foo.xml'));
        $this->assertStringContainsString(
            'src/foo.xml manifest has been updated',
            $this->logs->getText(Project::MSG_WARN)
        );
    }

    public function testVersionAttributeInSingleQuotes()
    {
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile(
            'src/foo.xml',
            str_replace('<extension type', "<extension version='3.9' type", static::STANDARD_MANIFEST)
        );

        $this->runTask('8.2');

        $this->assertSame(static::STANDARD_MANIFEST, $this->readFile('src/foo.xml'));
    }

    public function testVersionTagIsNotMistakenForAttribute()
    {
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile('src/foo.xml', static::STANDARD_MANIFEST);

        $this->runTask('8.2');

        $this->assertStringContainsString('<version>1.2.3</version>', $this->readFile('src/foo.xml'));
    }

    public function testSingleLineManifest()
    {
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile(
            'src/foo.xml',
            '<extension version="3.9" type="plugin"><name>plg_system_foo</name><version>1.2.3</version></extension>'
        );

        $this->runTask('8.2');

        $manifest = simplexml_load_file($this->tmpPath . '/src/foo.xml');
        $this->assertNotFalse($manifest, $this->readFile('src/foo.xml'));
        $this->assertSame('Joomlashack', (string)$manifest->author);
        $this->assertSame('1.2.3', (string)$manifest->version);
        $this->assertSame('', (string)$manifest['version']);
    }

    public function testMissingRequiredHeadersAreAdded()
    {
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile(
            'src/foo.xml',
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="system" method="upgrade">
    <version>1.2.3</version>
    <creationDate>January 1, 2020</creationDate>
</extension>

XML
        );

        $this->runTask('8.2');

        $manifest = simplexml_load_file($this->tmpPath . '/src/foo.xml');
        $this->assertSame('', (string)$manifest->name);
        $this->assertSame('', (string)$manifest->description);
        $this->assertSame('', (string)$manifest->copyright);
        $this->assertSame('Joomlashack', (string)$manifest->author);
        $this->assertSame('help@joomlashack.com', (string)$manifest->authorEmail);
        $this->assertSame('https://www.joomlashack.com/', (string)$manifest->authorUrl);
        $this->assertSame('GNU GPL; see LICENSE file', (string)$manifest->license);
        $this->assertSame('1.2.3', (string)$manifest->version);
    }

    public function testOptionalHeadersAreKeptInOrder()
    {
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile(
            'src/foo.xml',
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="library" method="upgrade">
    <variant>PRO</variant>
    <libraryname>foo</libraryname>
    <name>lib_foo</name>
    <version>1.2.3</version>
    <creationDate>January 1, 2020</creationDate>
</extension>

XML
        );

        $this->runTask('8.2');

        $manifest = $this->readFile('src/foo.xml');
        $order    = ['<name>', '<libraryname>', '<author>', '<copyright>', '<license>', '<description>', '<version>', '<creationDate>', '<variant>'];

        $positions = array_map(fn($tag) => strpos($manifest, $tag), $order);
        $this->assertNotContains(false, $positions, $manifest);

        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, $manifest);
    }

    public function testEmbeddedRelatedManifestsAreStandardized()
    {
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile(
            'src/foo.xml',
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="component" method="upgrade">
    <name>com_foo</name>
    <author>Joomlashack</author>
    <authorEmail>help@joomlashack.com</authorEmail>
    <authorUrl>https://www.joomlashack.com/</authorUrl>
    <copyright>Copyright 2026 Joomlashack.com</copyright>
    <license>GNU GPL; see LICENSE file</license>
    <description>COM_FOO_DESCRIPTION</description>
    <version>2.0.0</version>
    <creationDate>March 3, 2026</creationDate>
    <alledia>
        <relatedExtensions>
            <extension type="plugin" group="content" element="foo">plg_content_foo</extension>
            <extension type="module" element="foofiles">mod_foofiles</extension>
            <extension type="library" element="external">External</extension>
        </relatedExtensions>
    </alledia>
</extension>

XML
        );

        $related = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="%s" method="upgrade">
    <name>%s</name>
    <author>Somebody</author>
    <version>1.0.0</version>
    <creationDate>January 1, 2020</creationDate>
</extension>

XML;
        $this->writeFile('src/extensions/plg_content_foo/foo.xml', sprintf($related, 'plugin', 'plg_content_foo'));
        $this->writeFile('src/extensions/mod_foofiles/mod_foofiles.xml', sprintf($related, 'module', 'mod_foofiles'));

        $this->runTask('8.2', 'plg_content_foo,mod_foofiles,External');

        foreach (['plg_content_foo/foo.xml', 'mod_foofiles/mod_foofiles.xml'] as $file) {
            $manifest = simplexml_load_file($this->tmpPath . '/src/extensions/' . $file);

            $this->assertSame('Joomlashack', (string)$manifest->author, $file);

            // Related extensions get the primary extension's version and date
            $this->assertSame('2.0.0', (string)$manifest->version, $file);
            $this->assertSame('March 3, 2026', (string)$manifest->creationDate, $file);

            // Copyright is copied from the primary manifest
            $this->assertSame('Copyright 2026 Joomlashack.com', (string)$manifest->copyright, $file);
        }
    }

    public function testManifestWithoutRelatedExtensions()
    {
        // project.relatedExtensions is not set when the manifest has none
        $this->writeFile('composer.json', static::STANDARD_COMPOSER);
        $this->writeFile('src/foo.xml', static::STANDARD_MANIFEST);

        $task = $this->createTask(StandardsFixesTask::class);
        $task->setManifest($this->tmpPath . '/src/foo.xml');
        $task->setPhp('8.2');

        $this->setProperties([
            'project.path'        => $this->tmpPath,
            'project.source.path' => $this->tmpPath . '/src',
        ]);
        $task->main();

        $this->assertSame(static::STANDARD_MANIFEST, $this->readFile('src/foo.xml'));
    }

    /**
     * @param ?string $php
     * @param string  $related
     *
     * @return void
     */
    protected function runTask(?string $php, string $related = '')
    {
        $this->setProperties([
            'project.path'              => $this->tmpPath,
            'project.source.path'       => $this->tmpPath . '/src',
            'project.relatedExtensions' => $related,
        ]);

        $task = $this->createTask(StandardsFixesTask::class);
        $task->setManifest($this->tmpPath . '/src/foo.xml');
        if ($php !== null) {
            $task->setPhp($php);
        }
        $task->main();
    }
}

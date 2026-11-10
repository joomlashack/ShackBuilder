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

use Exception;
use ShackBuilder\Tasks\RelatedExtensionsTask;
use ShackBuilder\Tests\Support\TaskTestCase;

class RelatedExtensionsTaskTest extends TaskTestCase
{
    protected const RELATED = <<<XML
        <relatedExtensions>
            <extension type="library" element="allediaframework">ShackFramework</extension>
            <extension type="plugin" group="system" element="osmylicensesmanager" updateversion="false">ShackExtensionSupport</extension>
            <extension type="module" element="foofiles" updateversion="true">mod_foofiles</extension>
        </relatedExtensions>
XML;

    public function testRelatedExtensionsAreListed()
    {
        $this->runTask($this->writeManifest(static::RELATED));

        $this->assertSame(
            'ShackFramework,ShackExtensionSupport,mod_foofiles',
            $this->project->getProperty('related')
        );
        $this->assertSame('allediaframework', $this->project->getProperty('related.ShackFramework.element'));
        $this->assertSame('osmylicensesmanager', $this->project->getProperty('related.ShackExtensionSupport.element'));
        $this->assertSame('foofiles', $this->project->getProperty('related.mod_foofiles.element'));

        $this->assertSame('', $this->project->getProperty('related.ShackFramework.updateversion'));
        $this->assertSame('false', $this->project->getProperty('related.ShackExtensionSupport.updateversion'));
        $this->assertSame('true', $this->project->getProperty('related.mod_foofiles.updateversion'));
    }

    public function testIgnoredExtensionsAreLeftOut()
    {
        $this->runTask($this->writeManifest(static::RELATED), 'ShackFramework,mod_foofiles');

        $this->assertSame('ShackExtensionSupport', $this->project->getProperty('related'));
        $this->assertStringContainsString(
            'Ignored some related extensions: ShackFramework,mod_foofiles',
            $this->logs->getText()
        );
    }

    public function testUnexpandedIgnorePropertyIsTreatedAsEmpty()
    {
        // build.xml passes ${ignoreRelatedExtensions}, which stays literal when not set
        $this->runTask($this->writeManifest(static::RELATED), '${ignoreRelatedExtensions}');

        $this->assertSame(
            'ShackFramework,ShackExtensionSupport,mod_foofiles',
            $this->project->getProperty('related')
        );
    }

    public function testNoRelatedExtensionsLeavesPropertyUnset()
    {
        $this->runTask($this->writeManifest(''));

        $this->assertNull($this->project->getProperty('related'));
    }

    public function testInvalidFileThrowsException()
    {
        $task = $this->createTask(RelatedExtensionsTask::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid XML file path');

        $task->setFile($this->tmpPath . '/missing.xml');
    }

    /**
     * @param string  $manifest
     * @param ?string $ignore
     *
     * @return void
     */
    protected function runTask(string $manifest, ?string $ignore = null)
    {
        $task = $this->createTask(RelatedExtensionsTask::class);
        $task->setFile($manifest);
        $task->setProperty('related');
        if ($ignore !== null) {
            $task->setIgnoreRelatedExtensions($ignore);
        }
        $task->main();
    }

    /**
     * @param string $alledia
     *
     * @return string
     */
    protected function writeManifest(string $alledia): string
    {
        return $this->writeFile(
            'foo.xml',
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="component" method="upgrade">
    <name>com_foo</name>
    <alledia>
        <element>foo</element>
{$alledia}
    </alledia>
</extension>
XML
        );
    }
}

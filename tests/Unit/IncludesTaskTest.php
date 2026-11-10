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
use ShackBuilder\Tasks\IncludesTask;
use ShackBuilder\Tests\Support\TaskTestCase;

class IncludesTaskTest extends TaskTestCase
{
    protected const INCLUDES = <<<XML
        <include>ShackDefaultFiles</include>
        <include source="library/" destination="admin/library">Shared</include>
        <include destination="">Other</include>
XML;

    public function testIncludesAreListed()
    {
        $this->runTask($this->writeManifest(static::INCLUDES));

        $this->assertSame('ShackDefaultFiles,Shared,Other', $this->project->getProperty('includes'));
    }

    public function testAttributesBecomeProperties()
    {
        $this->runTask($this->writeManifest(static::INCLUDES));

        $this->assertSame('library/', $this->project->getProperty('includes.Shared.source'));
        $this->assertSame('admin/library', $this->project->getProperty('includes.Shared.destination'));
    }

    public function testEmptyAttributesAreSkipped()
    {
        $this->runTask($this->writeManifest(static::INCLUDES));

        $this->assertNull($this->project->getProperty('includes.Other.destination'));
        $this->assertNull($this->project->getProperty('includes.ShackDefaultFiles.source'));
    }

    public function testIgnoredIncludesAreLeftOut()
    {
        $this->runTask($this->writeManifest(static::INCLUDES), 'ShackDefaultFiles');

        $this->assertSame('Shared,Other', $this->project->getProperty('includes'));
        $this->assertStringContainsString('Ignored some includes: ShackDefaultFiles', $this->logs->getText());
    }

    public function testUnexpandedIgnorePropertyIsTreatedAsEmpty()
    {
        $this->runTask($this->writeManifest(static::INCLUDES), '${ignoreIncludes}');

        $this->assertSame('ShackDefaultFiles,Shared,Other', $this->project->getProperty('includes'));
    }

    public function testNoIncludesLeavesPropertyUnset()
    {
        $this->runTask($this->writeManifest(''));

        $this->assertNull($this->project->getProperty('includes'));
    }

    public function testInvalidFileThrowsException()
    {
        $task = $this->createTask(IncludesTask::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid XML file path');

        $task->setFile('');
    }

    /**
     * @param string  $manifest
     * @param ?string $ignore
     *
     * @return void
     */
    protected function runTask(string $manifest, ?string $ignore = null)
    {
        $task = $this->createTask(IncludesTask::class);
        $task->setFile($manifest);
        $task->setProperty('includes');
        if ($ignore !== null) {
            $task->setIgnoreIncludes($ignore);
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

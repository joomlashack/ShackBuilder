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

use ShackBuilder\Tasks\MergeFilesTask;
use ShackBuilder\Tests\Support\TaskTestCase;

class MergeFilesTaskTest extends TaskTestCase
{
    public function testProFilesAreAppendedAndRemoved()
    {
        $this->writeFile('en-GB/en-GB.plg_system_foo.ini', "FREE=\"Free\"\n");
        $this->writeFile('en-GB/en-GB.plg_system_foo.pro.ini', "PRO=\"Pro\"\n");
        $this->writeFile('en-GB/en-GB.plg_system_foo.sys.ini', "FREE_SYS=\"Free\"\n");
        $this->writeFile('en-GB/en-GB.plg_system_foo.pro.sys.ini', "PRO_SYS=\"Pro\"\n");

        $this->runTask($this->tmpPath . '/en-GB');

        $this->assertSame("FREE=\"Free\"\nPRO=\"Pro\"\n", $this->readFile('en-GB/en-GB.plg_system_foo.ini'));
        $this->assertSame(
            "FREE_SYS=\"Free\"\nPRO_SYS=\"Pro\"\n",
            $this->readFile('en-GB/en-GB.plg_system_foo.sys.ini')
        );
        $this->assertFileDoesNotExist($this->tmpPath . '/en-GB/en-GB.plg_system_foo.pro.ini');
        $this->assertFileDoesNotExist($this->tmpPath . '/en-GB/en-GB.plg_system_foo.pro.sys.ini');
        $this->assertStringContainsString(
            'Merged files: en-GB.plg_system_foo.pro.ini -> en-GB.plg_system_foo.ini',
            $this->logs->getText()
        );
    }

    public function testProFileWithoutFreeFileIsLeftAlone()
    {
        $this->writeFile('en-GB/en-GB.plg_system_foo.pro.ini', "PRO=\"Pro\"\n");

        $this->runTask($this->tmpPath . '/en-GB');

        $this->assertSame("PRO=\"Pro\"\n", $this->readFile('en-GB/en-GB.plg_system_foo.pro.ini'));
        $this->assertFileDoesNotExist($this->tmpPath . '/en-GB/en-GB.plg_system_foo.ini');
    }

    public function testFilesWithoutPatternAreUntouched()
    {
        $this->writeFile('en-GB/en-GB.plg_system_foo.ini', "FREE=\"Free\"\n");

        $this->runTask($this->tmpPath . '/en-GB');

        $this->assertSame("FREE=\"Free\"\n", $this->readFile('en-GB/en-GB.plg_system_foo.ini'));
        $this->assertSame([], $this->logs->getMessages());
    }

    public function testFoldersMatchingPatternAreNotMerged()
    {
        mkdir($this->tmpPath . '/base/folder.pro.x', 0777, true);
        mkdir($this->tmpPath . '/base/folder.x');
        $this->writeFile('base/file.txt', 'base');

        // Run from somewhere other than the base path, as the build does
        $cwd = getcwd();
        chdir(static::BUILDER_ROOT);
        try {
            $this->runTask($this->tmpPath . '/base');

        } finally {
            chdir($cwd);
        }

        $this->assertDirectoryExists($this->tmpPath . '/base/folder.pro.x');
        $this->assertDirectoryExists($this->tmpPath . '/base/folder.x');
    }

    /**
     * @param string $path
     *
     * @return void
     */
    protected function runTask(string $path)
    {
        $task = $this->createTask(MergeFilesTask::class);
        $task->setBasePath($path);
        $task->setPattern('.pro.');
        $task->setReplace('.');
        $task->main();
    }
}

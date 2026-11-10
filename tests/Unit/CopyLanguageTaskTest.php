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

use FilesystemIterator;
use Phing\Project;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ShackBuilder\Tasks\CopyLanguageTask;
use ShackBuilder\Tests\Support\TaskTestCase;

class CopyLanguageTaskTest extends TaskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setProperties([
            'project.source.path' => $this->tmpPath . '/src',
            'project.basedir'     => $this->tmpPath,
            'project.name.short'  => 'foo',
        ]);

        $this->writeFile('src/admin/language/en-GB/en-GB.com_foo.ini', 'ADMIN="Admin"');
        $this->writeFile('src/admin/language/en-GB/en-GB.com_foo.sys.ini', 'SYS="Sys"');
        $this->writeFile('src/site/language/en-GB/en-GB.com_foo.ini', 'SITE="Site"');
        $this->writeFile('src/site/language/de-DE/de-DE.com_foo.ini', 'SITE="Seite"');
        $this->writeFile('src/admin/foo.php', '<?php');
    }

    public function testLanguageFilesAreCopiedToNewCode()
    {
        $this->runTask('en-GB', 'fr-FR');

        $target = 'foo.language.fr-FR';
        $this->assertSame('ADMIN="Admin"', $this->readFile($target . '/admin/language/fr-FR/fr-FR.com_foo.ini'));
        $this->assertSame('SYS="Sys"', $this->readFile($target . '/admin/language/fr-FR/fr-FR.com_foo.sys.ini'));
        $this->assertSame('SITE="Site"', $this->readFile($target . '/site/language/fr-FR/fr-FR.com_foo.ini'));

        $this->assertSame(3, $this->countFiles($this->tmpPath . '/' . $target));
        $this->assertStringContainsString('3 Files written to', $this->logs->getText(Project::MSG_WARN));
    }

    public function testSourceFilesAreUnchanged()
    {
        $this->runTask('en-GB', 'fr-FR');

        $this->assertSame(5, $this->countFiles($this->tmpPath . '/src'));
        $this->assertDirectoryDoesNotExist($this->tmpPath . '/src/admin/language/fr-FR');
    }

    public function testCustomFolders()
    {
        mkdir($this->tmpPath . '/out');

        $task = $this->createTask(CopyLanguageTask::class);
        $task->setFrom('de-DE');
        $task->setTo('nl-NL');
        $task->fromDir($this->tmpPath . '/src/site');
        $task->toDir($this->tmpPath . '/out');
        $task->main();

        $this->assertSame('SITE="Seite"', $this->readFile('out/foo.language.nl-NL/language/nl-NL/nl-NL.com_foo.ini'));
    }

    public function testInvalidCodeCopiesNothing()
    {
        $this->runTask('en-GB', 'french');

        $this->assertDirectoryDoesNotExist($this->tmpPath . '/foo.language.french');
        $this->assertStringContainsString('Invalid language code entered', $this->logs->getText(Project::MSG_ERR));
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return void
     */
    protected function runTask(string $from, string $to)
    {
        $task = $this->createTask(CopyLanguageTask::class);
        $task->setFrom($from);
        $task->setTo($to);
        $task->main();
    }

    /**
     * @param string $path
     *
     * @return int
     */
    protected function countFiles(string $path): int
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        return iterator_count($files);
    }
}

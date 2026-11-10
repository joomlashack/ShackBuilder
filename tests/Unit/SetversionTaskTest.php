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

use Phing\Io\File;
use PHPUnit\Framework\Attributes\DataProvider;
use ShackBuilder\Tasks\SetversionTask;
use ShackBuilder\Tests\Support\TaskTestCase;

class SetversionTaskTest extends TaskTestCase
{
    public static function versionProvider(): array
    {
        return [
            'major'                    => ['1.2.3', 'major', '2.0.0'],
            'minor'                    => ['1.2.3', 'minor', '1.3.0'],
            'bugfix'                   => ['1.2.3', 'bugfix', '1.2.4'],
            'release type is case-insensitive' => ['1.2.3', 'BugFix', '1.2.4'],
            'first alpha'              => ['1.2.3', 'a', '1.2.3a1'],
            'next alpha'               => ['1.2.3a1', 'a', '1.2.3a2'],
            'alpha to beta'            => ['1.2.3a2', 'b', '1.2.3b1'],
            'next beta'                => ['1.2.3b1', 'b', '1.2.3b2'],
            'beta to rc'               => ['1.2.3b2', 'rc', '1.2.3rc1'],
            'next rc'                  => ['1.2.3rc1', 'rc', '1.2.3rc2'],
            'rc to stable'             => ['1.2.3rc2', 'stable', '1.2.3'],
            'stable of stable'         => ['1.2.3', 'stable', '1.2.3'],
            'major drops build'        => ['1.2.3b4', 'major', '2.0.0'],
            'minor drops build'        => ['1.2.3rc1', 'minor', '1.3.0'],
            'bugfix drops build'       => ['1.2.3a9', 'bugfix', '1.2.4'],
            'multi-digit parts'        => ['10.20.30', 'bugfix', '10.20.31'],
            'multi-digit build'        => ['1.0.0b9', 'b', '1.0.0b10'],
            'suffix is kept'           => ['1.2.3-dev', 'bugfix', '1.2.4-dev'],
        ];
    }

    #[DataProvider('versionProvider')]
    public function testVersionIsIncremented(string $current, string $releaseType, string $expected)
    {
        $manifest = $this->writeManifest($current);

        $task = $this->createTask(SetversionTask::class);
        $task->setReleasetype($releaseType);
        $task->setFile(new File($manifest));
        $task->setProperty('new.version');
        $task->main();

        $this->assertSame($expected, $this->project->getProperty('new.version'));
        $this->assertStringContainsString(
            '<version>' . $expected . '</version>',
            file_get_contents($manifest)
        );
    }

    public function testCustomVersion()
    {
        $manifest = $this->writeManifest('1.2.3');

        $task = $this->createTask(SetversionTask::class);
        $task->setReleasetype('custom');
        $task->setCustomvalue('5.0.0b1');
        $task->setFile(new File($manifest));
        $task->setProperty('new.version');
        $task->main();

        $this->assertSame('5.0.0b1', $this->project->getProperty('new.version'));
        $this->assertStringContainsString('<version>5.0.0b1</version>', file_get_contents($manifest));
    }

    public function testOnlyTheVersionTagChanges()
    {
        $manifest = $this->writeManifest('1.2.3');
        $before   = file_get_contents($manifest);

        $task = $this->createTask(SetversionTask::class);
        $task->setReleasetype('minor');
        $task->setFile(new File($manifest));
        $task->setProperty('new.version');
        $task->main();

        $this->assertSame(
            str_replace('<version>1.2.3</version>', '<version>1.3.0</version>', $before),
            file_get_contents($manifest)
        );
        $this->assertStringContainsString('to new version: 1.3.0', $this->logs->getText());
    }

    public function testWhitespaceInsideVersionTagIsKept()
    {
        $manifest = $this->writeFile(
            'manifest.xml',
            "<extension>\n    <version>\n        1.2.3</version>\n</extension>\n"
        );

        $task = $this->createTask(SetversionTask::class);
        $task->setReleasetype('bugfix');
        $task->setFile(new File($manifest));
        $task->setProperty('new.version');
        $task->main();

        $this->assertSame('1.2.4', $this->project->getProperty('new.version'));
        $this->assertSame(
            "<extension>\n    <version>\n        1.2.4</version>\n</extension>\n",
            file_get_contents($manifest)
        );
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function writeManifest(string $version): string
    {
        return $this->writeFile(
            'manifest.xml',
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="system" method="upgrade">
    <name>plg_system_foo</name>
    <version>{$version}</version>
    <creationDate>January 1, 2020</creationDate>
</extension>

XML
        );
    }
}

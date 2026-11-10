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
use ShackBuilder\Tasks\PropertiesFromComposerTask;
use ShackBuilder\Tests\Support\TaskTestCase;

class PropertiesFromComposerTaskTest extends TaskTestCase
{
    public function testComposerValuesBecomeProperties()
    {
        $path = $this->writeComposer([
            'name'    => 'joomlashack/foo',
            'type'    => 'joomla-plugin',
            'extra'   => [
                'element'         => 'plg_system_foo',
                'element-short'   => 'foo',
                'package-license' => 'pro',
            ],
            'require' => [
                'php' => '>=8.1',
            ],
        ]);

        $task = $this->createTask(PropertiesFromComposerTask::class);
        $task->setFile($path);
        $task->setPrefix('composer');
        $task->main();

        $this->assertSame('joomlashack/foo', $this->project->getProperty('composer.name'));
        $this->assertSame('joomla-plugin', $this->project->getProperty('composer.type'));
        $this->assertSame('plg_system_foo', $this->project->getProperty('composer.extra.element'));
        $this->assertSame('foo', $this->project->getProperty('composer.extra.element-short'));
        $this->assertSame('pro', $this->project->getProperty('composer.extra.package-license'));
        $this->assertSame('>=8.1', $this->project->getProperty('composer.require.php'));
        $this->assertSame('plg', $this->project->getProperty('project.type'));
    }

    public function testPrefixDefaultsToComposer()
    {
        $path = $this->writeComposer(['name' => 'joomlashack/foo', 'type' => 'joomla-module']);

        $task = $this->createTask(PropertiesFromComposerTask::class);
        $task->setFile($path);
        $task->main();

        $this->assertSame('joomlashack/foo', $this->project->getProperty('composer.name'));
    }

    public function testCustomPrefix()
    {
        $path = $this->writeComposer(['name' => 'joomlashack/foo', 'type' => 'joomla-module']);

        $task = $this->createTask(PropertiesFromComposerTask::class);
        $task->setFile($path);
        $task->setPrefix('other');
        $task->main();

        $this->assertSame('joomlashack/foo', $this->project->getProperty('other.name'));
        $this->assertNull($this->project->getProperty('composer.name'));
    }

    public static function typeProvider(): array
    {
        return [
            ['joomla-plugin', 'plg'],
            ['joomla-module', 'mod'],
            ['joomla-template', 'tpl'],
            ['joomla-component', 'com'],
            ['joomla-package', 'pkg'],
            ['joomla-file', 'file'],
            ['joomla-cli', 'cli'],
            ['joomla-library', 'lib'],
        ];
    }

    #[DataProvider('typeProvider')]
    public function testProjectTypeFromComposerType(string $composerType, string $projectType)
    {
        $path = $this->writeComposer(['type' => $composerType]);

        $task = $this->createTask(PropertiesFromComposerTask::class);
        $task->setFile($path);
        $task->main();

        $this->assertSame($projectType, $this->project->getProperty('project.type'));
    }

    public function testDotsInTypeAreFixedInTheFile()
    {
        $path = $this->writeComposer(['name' => 'joomlashack/foo', 'type' => 'joomla.plugin']);

        $task = $this->createTask(PropertiesFromComposerTask::class);
        $task->setFile($path);
        $task->main();

        $this->assertSame('joomla-plugin', $this->project->getProperty('composer.type'));
        $this->assertSame('plg', $this->project->getProperty('project.type'));
        $this->assertStringContainsString('"type": "joomla-plugin"', file_get_contents($path));
        $this->assertStringContainsString(
            'composer.json has been updated',
            $this->logs->getText(Project::MSG_WARN)
        );
    }

    public function testValidTypeLeavesFileUnchanged()
    {
        $path   = $this->writeComposer(['name' => 'joomlashack/foo', 'type' => 'joomla-plugin']);
        $before = file_get_contents($path);

        $task = $this->createTask(PropertiesFromComposerTask::class);
        $task->setFile($path);
        $task->main();

        $this->assertSame($before, file_get_contents($path));
        $this->assertSame([], $this->logs->getMessages(Project::MSG_WARN));
    }

    /**
     * @param array $composer
     *
     * @return string
     */
    protected function writeComposer(array $composer): string
    {
        return $this->writeFile('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

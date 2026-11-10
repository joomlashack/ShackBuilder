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

use ShackBuilder\Tasks\TemplateDetailsTask;
use ShackBuilder\Tasks\TemplateVersionTask;
use ShackBuilder\Tests\Support\TaskTestCase;

/**
 * TemplateDetailsTask and TemplateVersionTask, used for Wright templates
 */
class TemplateTasksTest extends TaskTestCase
{
    public function testTemplateDetailsUpdatesWrightFiles()
    {
        $this->writeFile('wright/wright.php', '<?php $version = "{version}";');
        $this->writeFile(
            'wrighttemplate.php',
            '<?php $name = "{templateName}"; $docs = "{documentationLink}";'
        );

        $task = $this->createTask(TemplateDetailsTask::class);
        $task->setTodir($this->tmpPath);
        $task->setVersion('1.0.0_3.0.0');
        $task->setTemplateName('Foo');
        $task->setDocumentationLink('https://www.joomlashack.com/docs/foo');
        $task->main();

        $this->assertSame('<?php $version = "1.0.0_3.0.0";', $this->readFile('wright/wright.php'));
        $this->assertSame(
            '<?php $name = "Foo"; $docs = "https://www.joomlashack.com/docs/foo";',
            $this->readFile('wrighttemplate.php')
        );
    }

    public function testTemplateDetailsSkipsMissingFiles()
    {
        $task = $this->createTask(TemplateDetailsTask::class);
        $task->setTodir($this->tmpPath);
        $task->setVersion('1.0.0');
        $task->main();

        $this->assertSame([], $this->logs->getMessages());
    }

    public function testTemplateVersionUpdatesLanguageAndWrightFiles()
    {
        $languages = ['en-GB', 'es-ES', 'de-DE'];
        foreach ($languages as $code) {
            $this->writeFile("language/{$code}/{$code}.tpl_foo.ini", 'TPL_FOO_VERSION="{version}"');
            $this->writeFile("language/{$code}/{$code}.tpl_foo.sys.ini", 'TPL_FOO_SYS="v{version}"');
        }
        $this->writeFile('wright/wright.php', '<?php $version = "{version}";');

        $task = $this->createTask(TemplateVersionTask::class);
        $task->setTodir($this->tmpPath);
        $task->setTemplate('foo');
        $task->setVersion('1.2.3');
        $task->main();

        foreach ($languages as $code) {
            $this->assertSame('TPL_FOO_VERSION="1.2.3"', $this->readFile("language/{$code}/{$code}.tpl_foo.ini"));
            $this->assertSame('TPL_FOO_SYS="v1.2.3"', $this->readFile("language/{$code}/{$code}.tpl_foo.sys.ini"));
        }
        $this->assertSame('<?php $version = "1.2.3";', $this->readFile('wright/wright.php'));
    }

    public function testTemplateVersionOnlyHandlesKnownLanguages()
    {
        $this->writeFile('language/fr-FR/fr-FR.tpl_foo.ini', 'TPL_FOO_VERSION="{version}"');
        $this->writeFile('language/en-GB/en-GB.tpl_foo.ini', 'TPL_FOO_VERSION="{version}"');

        $task = $this->createTask(TemplateVersionTask::class);
        $task->setTodir($this->tmpPath);
        $task->setTemplate('foo');
        $task->setVersion('1.2.3');
        $task->main();

        $this->assertSame('TPL_FOO_VERSION="1.2.3"', $this->readFile('language/en-GB/en-GB.tpl_foo.ini'));
        $this->assertSame('TPL_FOO_VERSION="{version}"', $this->readFile('language/fr-FR/fr-FR.tpl_foo.ini'));
    }
}

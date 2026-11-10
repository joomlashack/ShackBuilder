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

use ShackBuilder\Tasks\MergeMinifyTask;
use ShackBuilder\Tests\Support\TaskTestCase;

class MergeMinifyTaskTest extends TaskTestCase
{
    protected const SCRIPT_A = <<<JS
// First script
function first(value) {
    return value + 1;
}

JS;

    protected const SCRIPT_B = <<<JS
/* Second script */
var second = function (value) {
    return value * 2;
};

JS;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writeFile('src/media/js/a.js', static::SCRIPT_A);
        $this->writeFile('src/media/js/b.js', static::SCRIPT_B);
    }

    public function testSingleScriptIsMinified()
    {
        $this->runTask('<minify><script>media/js/a.js</script></minify>');

        $minified = $this->readFile('src/media/js/a.min.js');
        $this->assertStringContainsString('function first(value){return value+1;}', $minified);
        $this->assertStringNotContainsString('First script', $minified);

        // Source is not changed
        $this->assertSame(static::SCRIPT_A, $this->readFile('src/media/js/a.js'));
    }

    public function testSeveralSingleScripts()
    {
        $this->runTask('<minify><script>media/js/a.js</script><script>media/js/b.js</script></minify>');

        $this->assertFileExists($this->tmpPath . '/src/media/js/a.min.js');
        $this->assertFileExists($this->tmpPath . '/src/media/js/b.min.js');
    }

    public function testCustomSuffix()
    {
        $this->runTask('<minify><script>media/js/a.js</script></minify>', '-min');

        $this->assertFileExists($this->tmpPath . '/src/media/js/a-min.js');
        $this->assertFileDoesNotExist($this->tmpPath . '/src/media/js/a.min.js');
    }

    public function testBundleIsMergedInOrderThenMinified()
    {
        $this->runTask(
            <<<XML
<minify>
    <scripts destination="media/js/bundle.js">
        <script>media/js/b.js</script>
        <script>media/js/a.js</script>
    </scripts>
</minify>
XML
        );

        // Each script is followed by a line break when appended
        $this->assertSame(
            static::SCRIPT_B . "\n" . static::SCRIPT_A . "\n",
            $this->readFile('src/media/js/bundle.js')
        );

        $minified = $this->readFile('src/media/js/bundle.min.js');
        $this->assertLessThan(
            strpos($minified, 'function first'),
            strpos($minified, 'var second'),
            'Scripts should be bundled in the listed order'
        );
    }

    public function testBundleIsRebuiltFromScratch()
    {
        $this->writeFile('src/media/js/bundle.js', "var stale = true;\n");

        $this->runTask(
            '<minify><scripts destination="media/js/bundle.js"><script>media/js/a.js</script></scripts></minify>'
        );

        $this->assertSame(static::SCRIPT_A . "\n", $this->readFile('src/media/js/bundle.js'));
    }

    public function testNoMinifyTagDoesNothing()
    {
        $this->runTask('');

        $this->assertSame(
            ['a.js', 'b.js'],
            array_values(array_diff(scandir($this->tmpPath . '/src/media/js'), ['.', '..']))
        );
    }

    /**
     * @param string  $minify
     * @param ?string $suffix
     *
     * @return void
     */
    protected function runTask(string $minify, ?string $suffix = null)
    {
        $manifest = $this->writeFile(
            'src/foo.xml',
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="system" method="upgrade">
    <name>plg_system_foo</name>
    <alledia>
        {$minify}
    </alledia>
</extension>
XML
        );

        $task = $this->createTask(MergeMinifyTask::class);
        $task->setManifest($manifest);
        $task->setBasePath($this->tmpPath . '/src');
        if ($suffix !== null) {
            $task->setSuffix($suffix);
        }
        $task->main();
    }
}

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
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use ShackBuilder\Tests\Support\PhingResult;
use ShackBuilder\Tests\Support\PhingTestCase;

/**
 * ForeachParallelTask forks, so it always runs in its own phing process
 */
#[RequiresPhpExtension('pcntl')]
class ForeachParallelTaskTest extends PhingTestCase
{
    /**
     * Writes ${value}.txt, holding the value and the absolute path, into out/
     */
    protected const WRITE_TARGET = <<<XML
    <target name="write">
        <echo file="\${project.basedir}/out/\${value}.txt" message="\${value}|\${abs}"/>
    </target>
XML;

    public static function threadProvider(): array
    {
        return [
            'one thread'           => [1],
            'two threads'          => [2],
            'more threads than items' => [10],
        ];
    }

    #[DataProvider('threadProvider')]
    public function testEveryListItemIsProcessed(int $threads)
    {
        $result = $this->runForeach(
            sprintf('<foreachParallel list="a,b,c,d,e" param="value" target="write" threadCount="%d"/>', $threads)
        );

        $this->assertBuildSucceeded($result);
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $this->outputValues());
    }

    public function testListItemsAreTrimmed()
    {
        $result = $this->runForeach('<foreachParallel list=" a , b " param="value" target="write"/>');

        $this->assertBuildSucceeded($result);
        $this->assertSame(['a', 'b'], $this->outputValues());
    }

    public function testCustomDelimiter()
    {
        $result = $this->runForeach('<foreachParallel list="a|b|c" delimiter="|" param="value" target="write"/>');

        $this->assertBuildSucceeded($result);
        $this->assertSame(['a', 'b', 'c'], $this->outputValues());
    }

    public function testFileset()
    {
        $this->writeFile('harness/files/one.js', '');
        $this->writeFile('harness/files/two.js', '');
        $this->writeFile('harness/files/skip.css', '');

        $result = $this->runForeach(
            <<<XML
<foreachParallel param="value" absparam="abs" target="write">
            <fileset dir="\${project.basedir}/files" includes="*.js"/>
        </foreachParallel>
XML
        );

        $this->assertBuildSucceeded($result);
        $this->assertSame(['one.js', 'two.js'], $this->outputValues());
        $this->assertSame(
            'one.js|' . $this->tmpPath . '/harness/files/one.js',
            $this->readFile('harness/out/one.js.txt')
        );
    }

    public function testFilelistWithMapper()
    {
        $this->writeFile('harness/files/one.scss', '');

        $result = $this->runForeach(
            <<<XML
<foreachParallel param="value" target="write">
            <mapper type="glob" from="*.scss" to="*.css"/>
            <filelist dir="\${project.basedir}/files" files="one.scss,two.scss"/>
        </foreachParallel>
XML
        );

        $this->assertBuildSucceeded($result);
        $this->assertSame(['one.css', 'two.css'], $this->outputValues());
    }

    public function testFailingItemFailsTheBuild()
    {
        $result = $this->runForeach(
            '<foreachParallel list="a,fail,c" param="value" target="maybe-fail" threadCount="2"/>',
            <<<XML
    <target name="maybe-fail">
        <fail if="fail.\${value}" message="Item \${value} failed"/>
        <echo file="\${project.basedir}/out/\${value}.txt" message="\${value}"/>
    </target>
XML,
            ['-Dfail.fail=1']
        );

        $this->assertSame(1, $result->exitCode, $result->output);
        $this->assertStringContainsString('Item fail failed', $result->output);
        $this->assertStringContainsString('A parallel task failed', $result->output);
    }

    public static function missingAttributeProvider(): array
    {
        return [
            'nothing to iterate' => [
                '<foreachParallel param="value" target="write"/>',
                'Need either list, nested fileset or nested filelist to iterate through',
            ],
            'no param'           => [
                '<foreachParallel list="a" target="write"/>',
                'You must supply a property name to set on each iteration in param',
            ],
            'no target'          => [
                '<foreachParallel list="a" param="value"/>',
                'You must supply a target to perform',
            ],
        ];
    }

    #[DataProvider('missingAttributeProvider')]
    public function testMissingAttributes(string $task, string $message)
    {
        $result = $this->runForeach($task);

        $this->assertSame(1, $result->exitCode, $result->output);
        $this->assertStringContainsString($message, $result->output);
    }

    /**
     * @param string   $task
     * @param string   $targets
     * @param string[] $args
     *
     * @return PhingResult
     */
    protected function runForeach(string $task, string $targets = self::WRITE_TARGET, array $args = []): PhingResult
    {
        mkdir($this->tmpPath . '/harness/out', 0777, true);

        $this->createBuildFile(
            'harness',
            "    <target name=\"run\">\n        {$task}\n    </target>\n\n{$targets}"
        );

        return $this->runPhing($this->tmpPath . '/harness', $args);
    }

    /**
     * @return string[] The values written by each iteration
     */
    protected function outputValues(): array
    {
        $values = [];
        foreach (glob($this->tmpPath . '/harness/out/*.txt') as $file) {
            $values[] = explode('|', file_get_contents($file))[0];
        }
        sort($values);

        return $values;
    }
}

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
use ShackBuilder\Tests\Support\PhingResult;
use ShackBuilder\Tests\Support\PhingTestCase;

/**
 * Setup mistakes and task errors. Tasks stop with die(1), so these need their own process.
 */
class ErrorsTest extends PhingTestCase
{
    public function testMissingBuildProperties()
    {
        $path = $this->createExtension('Foo');
        unlink($path . '/build.properties');

        $result = $this->runPhing($path);

        $this->assertBuildFailed($result, "Missing 'build.properties' file in the project root folder");
    }

    public function testMissingBuilderPath()
    {
        $path = $this->createExtension('Foo');
        file_put_contents($path . '/build.properties', "project.foo=bar\n");

        $result = $this->runPhing($path);

        $this->assertBuildFailed($result, "Missing 'builder.path' property");
    }

    public function testUnknownBuilderType()
    {
        $path = $this->createExtension('Foo', ['properties' => ['builder.type' => 'nonsense']]);

        $result = $this->runPhing($path);

        $this->assertBuildFailed($result, "Missing 'nonsense.xml' file in builder source path");
    }

    public function testComposerWithoutType()
    {
        $path = $this->createExtension('Foo');
        $this->removeComposerType($path);

        $result = $this->runPhing($path);

        $this->assertBuildFailed($result, 'Missing composer property: Unable to determine Joomla type');
    }

    public function testInvalidComposerType()
    {
        $path = $this->createExtension('Foo', ['composerType' => 'library']);

        $result = $this->runPhing($path);

        $this->assertBuildFailed($result, 'Invalid Joomla Extension Type: library');
    }

    public function testMissingComposerFile()
    {
        $path = $this->createExtension('Foo');
        unlink($path . '/composer.json');

        $result = $this->runPhing($path);

        $this->assertBuildFailed($result, 'Invalid composer.json file path');
    }

    public function testMissingManifest()
    {
        $path = $this->createExtension('Foo');
        unlink($path . '/src/foo.xml');

        $result = $this->runPhing($path);

        $this->assertBuildFailed($result);
    }

    public static function setVersionProvider(): array
    {
        return [
            'unknown release type'  => [
                '<setVersion releasetype="huge" file="manifest.xml" property="v"/>',
                'Unknown Releasetype HUGE',
            ],
            'missing release type'  => [
                '<setVersion file="manifest.xml" property="v"/>',
                'releasetype attribute is required',
            ],
            'missing property'      => [
                '<setVersion releasetype="minor" file="manifest.xml"/>',
                'Property for publishing version number is not set',
            ],
            'custom without value'  => [
                '<setVersion releasetype="custom" file="manifest.xml" property="v"/>',
                'Property for custom value is not set',
            ],
            'no version tag'        => [
                '<setVersion releasetype="minor" file="noversion.xml" property="v"/>',
                'Unable to find version tag',
            ],
            'empty file'            => [
                '<setVersion releasetype="minor" file="empty.xml" property="v"/>',
                'is empty',
            ],
        ];
    }

    #[DataProvider('setVersionProvider')]
    public function testSetVersionErrors(string $task, string $message)
    {
        $this->writeFile('harness/manifest.xml', '<extension><version>1.0.0</version></extension>');
        $this->writeFile('harness/noversion.xml', '<extension><name>foo</name></extension>');
        $this->writeFile('harness/empty.xml', '');

        $result = $this->runTask($task);

        $this->assertTaskFailed($result, $message);
    }

    public function testMergeMinifyErrors()
    {
        $this->assertTaskFailed(
            $this->runTask('<mergeMinify manifest="missing.xml" basePath="."/>'),
            'Invalid XML file path'
        );

        $this->writeFile('harness/manifest.xml', '<extension/>');
        $this->assertTaskFailed(
            $this->runTask('<mergeMinify manifest="manifest.xml" basePath="missing"/>'),
            'Invalid base path'
        );
    }

    public function testStandardsErrors()
    {
        $this->assertTaskFailed(
            $this->runTask('<standardsFixes manifest="missing.xml"/>'),
            'Manifest not found'
        );

        $this->writeFile('harness/manifest.xml', "<extension>\n    <version>1.0.0</version>\n</extension>\n");
        $this->assertTaskFailed(
            $this->runTask(
                '<property name="project.path" value="${project.basedir}"/>'
                . '<property name="project.relatedExtensions" value=""/>'
                . '<standardsFixes manifest="manifest.xml"/>'
            ),
            'composer.json not found'
        );
    }

    public function testStandardsWithBrokenManifest()
    {
        $path = $this->createExtension('Foo');
        file_put_contents(
            $path . '/src/foo.xml',
            str_replace('</extension>', '</extensions>', file_get_contents($path . '/src/foo.xml'))
        );

        $result = $this->runPhing($path, ['standards']);

        $this->assertBuildFailed($result);
    }

    /**
     * @param string $task
     *
     * @return PhingResult
     */
    protected function runTask(string $task): PhingResult
    {
        $this->createBuildFile('harness', "    <target name=\"run\">\n        {$task}\n    </target>");

        return $this->runPhing($this->tmpPath . '/harness');
    }

    /**
     * Tasks stop the build with die(1), so there is no BUILD FAILED line
     *
     * @param PhingResult $result
     * @param string      $message
     *
     * @return void
     */
    protected function assertTaskFailed(PhingResult $result, string $message)
    {
        $this->assertSame(1, $result->exitCode, $result->output);
        $this->assertStringContainsString($message, $result->output);
    }

    /**
     * @param string $path
     *
     * @return void
     */
    protected function removeComposerType(string $path)
    {
        $composer = json_decode(file_get_contents($path . '/composer.json'), true);
        unset($composer['type']);

        file_put_contents($path . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));
    }
}

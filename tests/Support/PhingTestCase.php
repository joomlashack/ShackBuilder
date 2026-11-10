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

namespace ShackBuilder\Tests\Support;

use ZipArchive;

/**
 * Runs phing as a separate process against fixture extension repos.
 *
 * Every test gets its own copy of the builder (symlinks to src/ and vendor/)
 * without global.properties, so the machine's own settings never leak in.
 * vendor/bin goes first on PATH, so recursive builds use the same phing.
 */
abstract class PhingTestCase extends TestCase
{
    /**
     * Path to the builder copy used as builder.path
     *
     * @var string
     */
    protected $builderPath = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $root = realpath(static::BUILDER_ROOT);

        $this->builderPath = $this->tmpPath . '/ShackBuilder';
        mkdir($this->builderPath);
        symlink($root . '/src', $this->builderPath . '/src');
        symlink($root . '/vendor', $this->builderPath . '/vendor');
    }

    /**
     * Create a fixture extension repo
     *
     * Options:
     *   type          Manifest type: plugin, module, component, library, template, file, package
     *   group         Plugin group
     *   element       composer extra.element (e.g. plg_system_foo)
     *   short         composer extra.element-short
     *   title         Manifest <name>, defaults to element
     *   name          composer extra.name
     *   license       free|pro
     *   version       Manifest version
     *   composerType  Defaults to joomla-<type>
     *   manifestName  Defaults to <short>.xml
     *   manifestExtra Extra XML inside <extension>
     *   alledia       Extra XML inside <alledia>
     *   files         [path relative to the repo => content]
     *   properties    Extra build.properties entries
     *   composer      Overrides for composer.json
     *
     * @param string $folder Folder name inside the scratch folder
     * @param array  $options
     *
     * @return string The repo path
     */
    protected function createExtension(string $folder, array $options = []): string
    {
        $options = array_merge(
            [
                'type'          => 'plugin',
                'group'         => 'system',
                'element'       => 'plg_system_foo',
                'short'         => 'foo',
                'title'         => null,
                'name'          => 'Foo',
                'license'       => 'free',
                'version'       => '1.2.3',
                'composerType'  => null,
                'manifestName'  => null,
                'manifestExtra' => '',
                'alledia'       => '',
                'files'         => [],
                'properties'    => [],
                'composer'      => [],
            ],
            $options
        );

        $path = $this->tmpPath . '/' . $folder;
        mkdir($path . '/src', 0777, true);

        copy(static::BUILDER_ROOT . '/src/clients/build.xml', $path . '/build.xml');

        $properties = array_merge(['builder.path' => $this->builderPath], $options['properties']);
        $this->writeFile($path . '/build.properties', $this->formatProperties($properties));

        $composer = array_replace_recursive(
            [
                'name'        => 'joomlashack/' . strtolower($options['name']),
                'description' => $options['name'],
                'license'     => 'GPL-2.0-or-later',
                'type'        => $options['composerType'] ?: 'joomla-' . $options['type'],
                'extra'       => [
                    'element'         => $options['element'],
                    'element-short'   => $options['short'],
                    'name'            => $options['name'],
                    'package-license' => $options['license'],
                    'target-platform' => '.*',
                ],
                'require'     => [
                    'php' => '>=8.1',
                ],
            ],
            $options['composer']
        );
        $this->writeFile(
            $path . '/composer.json',
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        $manifestName = $options['manifestName'] ?: $options['short'] . '.xml';
        $this->writeFile($path . '/src/' . $manifestName, $this->manifest($options));

        foreach ($options['files'] as $file => $content) {
            $this->writeFile($path . '/' . $file, $content);
        }

        return $path;
    }

    /**
     * @param array $options See createExtension()
     *
     * @return string
     */
    protected function manifest(array $options): string
    {
        $group = $options['type'] == 'plugin' ? sprintf(' group="%s"', $options['group']) : '';
        $title = $options['title'] ?: $options['element'];

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="{$options['type']}"{$group} method="upgrade">
    <name>{$title}</name>
    <author>Joomlashack</author>
    <authorEmail>help@joomlashack.com</authorEmail>
    <authorUrl>https://www.joomlashack.com/</authorUrl>
    <copyright>Copyright 2026 Joomlashack.com</copyright>
    <license>GNU GPL; see LICENSE file</license>
    <description>{$options['element']}_DESCRIPTION</description>
    <version>{$options['version']}</version>
    <creationDate>January 1, 2020</creationDate>
{$options['manifestExtra']}
    <alledia>
        <element>{$options['short']}</element>
{$options['alledia']}
    </alledia>
</extension>

XML;
    }

    /**
     * Create a stand-alone build file that has all the custom tasks registered
     *
     * @param string $folder  Folder name inside the scratch folder
     * @param string $targets Target definitions. The default target is "run"
     *
     * @return string The build file path
     */
    protected function createBuildFile(string $folder, string $targets): string
    {
        $root = realpath(static::BUILDER_ROOT);

        $taskDefinitions = [];
        foreach (glob($root . '/src/tasks/*Task.php') as $file) {
            $className = basename($file, '.php');

            $taskDefinitions[] = sprintf(
                '    <taskdef name="%s" classname="ShackBuilder\\Tasks\\%s"/>',
                lcfirst(substr($className, 0, -4)),
                $className
            );
        }
        $taskDefinitions = join("\n", $taskDefinitions);

        return $this->writeFile(
            $folder . '/build.xml',
            <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<project name="Harness" default="run">
    <autoloader autoloaderpath="{$root}/vendor/autoload.php"/>
{$taskDefinitions}

{$targets}
</project>

XML
        );
    }

    /**
     * @param array $properties
     *
     * @return string
     */
    protected function formatProperties(array $properties): string
    {
        $lines = [];
        foreach ($properties as $name => $value) {
            $lines[] = $name . '=' . $value;
        }

        return join("\n", $lines) . "\n";
    }

    /**
     * Run phing in a folder
     *
     * @param string   $path    Folder holding the build.xml
     * @param string[] $args    Command line arguments
     * @param string   $input   Answers for <input> prompts, one per line
     * @param bool     $fullPath Keep the full PATH (e.g. to find sass)
     *
     * @return PhingResult
     */
    protected function runPhing(string $path, array $args = [], string $input = '', bool $fullPath = false): PhingResult
    {
        $root = realpath(static::BUILDER_ROOT);

        $searchPath = [$root . '/vendor/bin', dirname(PHP_BINARY)];
        if ($fullPath) {
            $searchPath[] = getenv('PATH');
        } else {
            $searchPath[] = '/usr/bin:/bin:/usr/sbin:/sbin';
        }

        $environment = array_merge(
            getenv(),
            [
                'PATH'        => join(':', $searchPath),
                'XDEBUG_MODE' => 'off',
                // Creation dates use the locale's long date format
                'LC_ALL'      => 'en_US.UTF-8',
                'LANG'        => 'en_US.UTF-8',
            ]
        );

        $command = array_merge([PHP_BINARY, $root . '/vendor/bin/phing'], $args);

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['redirect', 1],
            ],
            $pipes,
            $path,
            $environment
        );

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        return new PhingResult(proc_close($process), $output);
    }

    /**
     * @param PhingResult $result
     *
     * @return void
     */
    protected function assertBuildSucceeded(PhingResult $result)
    {
        $this->assertSame(0, $result->exitCode, "Build failed:\n" . $result->output);
        $this->assertStringContainsString('BUILD FINISHED', $result->output);
    }

    /**
     * @param PhingResult $result
     * @param ?string     $message Expected text in the output
     *
     * @return void
     */
    protected function assertBuildFailed(PhingResult $result, ?string $message = null)
    {
        $this->assertNotSame(0, $result->exitCode, "Build should have failed:\n" . $result->output);

        if ($message !== null) {
            $this->assertStringContainsString($message, $result->output);
        }
    }

    /**
     * @param string $zipPath
     *
     * @return string[] Sorted file entries (folders left out)
     */
    protected function zipEntries(string $zipPath): array
    {
        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true, 'Unable to open ' . $zipPath);

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with($name, '/') == false) {
                $entries[] = $name;
            }
        }
        $zip->close();

        sort($entries);

        return $entries;
    }

    /**
     * @param string $zipPath
     * @param string $entry
     *
     * @return string
     */
    protected function zipContent(string $zipPath, string $entry): string
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true, 'Unable to open ' . $zipPath);

        $content = $zip->getFromName($entry);
        $zip->close();

        $this->assertNotFalse($content, $entry . ' not found in ' . basename($zipPath));

        return $content;
    }
}

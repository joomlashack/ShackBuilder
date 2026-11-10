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

use FilesystemIterator;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class TestCase extends BaseTestCase
{
    /**
     * Root of the ShackBuilder repository
     */
    public const BUILDER_ROOT = __DIR__ . '/../..';

    /**
     * Scratch folder for the current test, removed afterwards
     *
     * @var string
     */
    protected $tmpPath = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $path = sys_get_temp_dir() . '/shackbuilder-tests-' . bin2hex(random_bytes(6));
        mkdir($path, 0777, true);

        // Resolve symlinks (e.g. /var -> /private/var on macOS) so paths compare cleanly
        $this->tmpPath = realpath($path);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        if ($this->tmpPath && is_dir($this->tmpPath) && !getenv('SHACKBUILDER_KEEP_TMP')) {
            static::removeDirectory($this->tmpPath);
        }

        parent::tearDown();
    }

    /**
     * Write a file relative to the scratch folder (or absolute), creating folders as needed
     *
     * @param string $path
     * @param string $content
     *
     * @return string The absolute path
     */
    protected function writeFile(string $path, string $content): string
    {
        if (str_starts_with($path, '/') == false) {
            $path = $this->tmpPath . '/' . $path;
        }

        if (is_dir(dirname($path)) == false) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $content);

        return $path;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function readFile(string $path): string
    {
        if (str_starts_with($path, '/') == false) {
            $path = $this->tmpPath . '/' . $path;
        }

        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public static function removeDirectory(string $path)
    {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir() && $item->isLink() == false) {
                rmdir($item->getPathname());

            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}

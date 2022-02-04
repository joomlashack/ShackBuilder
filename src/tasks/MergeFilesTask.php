<?php
/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2016-2021 Joomlashack.com. All rights reserved
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

require_once 'phing/Task.php';

class MergeFilesTask extends Task
{
    /**
     * Base path, where both files will be
     *
     * @var string
     */
    protected $basePath;

    /**
     * The pattern to locate the file pair
     *
     * @var string
     */
    protected $pattern;

    /**
     * The string to replace the pattern
     *
     * @var string
     */
    protected $replace = '';

    /**
     * Set the base path
     *
     * @param string $path
     *
     * @return void
     */
    public function setBasePath(string $path)
    {
        $this->basePath = $path;
    }

    /**
     * Set the pattern to locate the file pair
     *
     * @param string $pattern The pattern
     *
     * @return void
     */
    public function setPattern(string $pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * Set the string to replace the pattern
     *
     * @param string $replace The string
     *
     * @return void
     */
    public function setReplace(string $replace = '')
    {
        $this->replace = $replace;
    }

    /**
     * @inheritDoc
     */
    public function main()
    {
        // Locate files on the base path that match the pattern
        $files   = scandir($this->basePath);
        $toMerge = [];

        foreach ($files as $file) {
            if (substr_count($file, $this->pattern)) {
                if (!is_dir($file)) {
                    $toMerge[] = $file;
                }
            }
        }

        // Merge the files
        if (!empty($toMerge)) {
            foreach ($toMerge as $file) {
                $target     = str_replace($this->pattern, $this->replace, $file);
                $targetPath = $this->basePath . '/' . $target;
                $filePath   = $this->basePath . '/' . $file;

                if (file_exists($targetPath)) {
                    $originalContent = file_get_contents($targetPath);
                    $newContent      = file_get_contents($filePath);

                    $originalContent .= $newContent;

                    // Merge the content
                    file_put_contents($targetPath, $originalContent);
                    $this->log('Merged files: ' . $file . ' -> ' . $target);

                    // Remove file
                    unlink($filePath);
                }
            }
        }
    }
}

<?php

/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2020-2026 Joomlashack.com. All rights reserved
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

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require_once 'phing/tasks/system/CopyTask.php';
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class CopyLanguageTask extends Task
{
    /**
     * @var string
     */
    protected $from = null;

    /**
     * @var string
     */
    protected $to = null;

    /**
     * @var string
     */
    protected $fromDirectory = null;

    /**
     * @var string
     */
    protected $toDirectory = null;

    /**
     * @var string
     */
    protected $destination = null;

    /**
     * @param string $code
     *
     * @return void
     */
    public function setFrom($code)
    {
        $this->from = $code;
    }

    /**
     * @param string $code
     *
     * @return void
     */
    public function setTo($code)
    {
        $this->to = $code;
    }

    /**
     * @param string $directory
     *
     * @return void
     */
    public function fromDir($directory)
    {
        $this->fromDirectory = $directory;
    }

    /**
     * @param string $directory
     *
     * @return void
     */
    public function toDir($directory)
    {
        $this->toDirectory = $directory;
    }

    public function main()
    {
        $codeRegex = '/[a-z]{2}-[A-Z]{2}/';

        $this->log('Copy language files: ' . $this->from . ' => ' . $this->to, Project::MSG_WARN);

        if (
            preg_match($codeRegex, $this->from)
            && preg_match($codeRegex, $this->to)
        ) {
            $this->fromDirectory = $this->fromDirectory ?: $this->project->getProperty('project.source.path');
            $this->toDirectory   = $this->toDirectory ?: $this->project->getProperty('project.basedir');

            $this->destination = sprintf(
                '%s/%s.language.%s',
                $this->toDirectory,
                $this->project->getProperty('project.name.short'),
                $this->to
            );

            $sourceFiles = $this->getFileList();
            foreach ($sourceFiles as $sourceFile) {
                $targetFile = str_replace(
                    $this->fromDirectory,
                    '',
                    preg_replace("/{$this->from}/", $this->to, $sourceFile)
                );

                $this->copy($sourceFile, $this->destination . $targetFile);
            }

            $this->log(
                sprintf(
                    '%s Files written to %s',
                    count($sourceFiles),
                    $this->destination
                ),
                Project::MSG_WARN
            );

            return;
        }

        $this->log('Invalid language code entered', Project::MSG_ERR);
    }

    protected function getFileList(DirectoryIterator $di = null)
    {
        $di = $di ?: new DirectoryIterator($this->fromDirectory);

        $files = [];
        while ($di->valid()) {
            $file = $di->current();
            if ($file->isFile() && strpos($file->getFilename(), $this->from) !== false) {
                $files[] = $file->getRealPath();
            } elseif ($file->isDir() && !$file->isDot()) {
                $files = array_merge($files, $this->getFileList(new DirectoryIterator($file->getRealPath())));
            }
            $di->next();
        }

        return $files;
    }

    protected function copy($source, $target)
    {
        $dirs = array_filter(explode('/', str_replace($this->toDirectory, '', dirname($target))));

        $path = $this->toDirectory;
        do {
            $path .= '/' . array_shift($dirs);

            if (!is_dir($path)) {
                mkdir($path);
            }
        } while ($dirs);

        copy($source, $target);
    }
}

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
require_once 'TraitShack.php';

class MergeMinifyTask extends Task
{
    use TraitShack;

    /**
     * @var string
     */
    protected $manifest = null;

    /**
     * @var string
     */
    protected $basePath = null;

    /**
     * @var string
     */
    protected $suffix = '.min';

    /**
     * @param string $path
     *
     * @return void
     */
    public function setManifest(string $path)
    {
        if (empty($path) || !file_exists(realpath($path))) {
            $this->throwError('Invalid XML file path');
        }

        $this->manifest = $path;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setBasePath(string $path)
    {
        if (empty($path) || is_dir(realpath($path)) == false) {
            $this->throwError('Invalid base path');
        }

        $this->basePath = $path;
    }

    /**
     * @param string $suffix
     *
     * @return void
     */
    public function setSuffix(string $suffix)
    {
        $this->suffix = $suffix;
    }

    /**
     * @inheritDoc
     */
    public function main()
    {
        try {
            $xml = simplexml_load_file($this->manifest);

            // Single files
            if ($xml->alledia->minify->script) {
                foreach ($xml->alledia->minify->script as $script) {
                    $this->minify($script);
                }
            }

            // Scripts bundle
            if ($xml->alledia->minify->scripts) {
                /** @var AppendTask $append */
                $append = $this->project->createTask('append');
                $append->setOwningTarget($this->getOwningTarget());
                $append->setTaskName($this->getTaskName());
                $append->setLocation($this->getLocation());
                $append->init();

                foreach ($xml->alledia->minify->scripts as $bundle) {
                    if (!empty($bundle->script)) {
                        $destination = $bundle['destination'];

                        // Remove destination, if exists
                        $fullPath = $this->basePath . '/' . $destination;
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }

                        // Merge files
                        foreach ($bundle->script as $script) {
                            $append->setDestFile(new PhingFile($fullPath));
                            $append->setOverwrite(false);
                            $append->setFixLastLine(true);
                            $append->setEol('unix');
                            $append->setFile(new PhingFile($this->basePath . '/' . $script));
                            $append->main();
                        }

                        $this->minify($destination);
                    }
                }
            }

        } catch (Throwable $error) {
            $this->throwError($error->getMessage());
        }
    }

    /**
     * @param  string $file
     *
     * @return void
     */
    protected function minify(string $file)
    {
        try {
            /** @var JsMinTask $minify */
            $minify = $this->project->createTask('jsMin');
            if ($minify->init()) {
                $minify->setTargetDir($this->basePath);
                $minify->setFailonerror(true);
                $minify->setSuffix($this->suffix);

                $fileset = new Fileset();
                $fileset->setDir($this->basePath);
                $fileset->setIncludes($file);

                $minify->addFileSet($fileset);
                $minify->main();

            } else {
                $this->throwError('Unable to initialize JShrink');
            }

        } catch (Throwable $error) {
            $this->throwError($error->getMessage());
        }
    }
}

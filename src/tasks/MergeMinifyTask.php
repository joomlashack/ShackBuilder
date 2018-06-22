<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2016 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';

class MergeMinifyTask extends Task
{
    /**
     * XML Manifest file
     *
     * @var string
     */
    protected $manifest;

    /**
     * Base path, usually the path to the src folder.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Suffix for minified files
     *
     * @var string
     */
    protected $suffix = '.min';

    /**
     * The setter for the attribute "manifest". It should point to
     * the composer.json file
     *
     * @param string $path The path for the manifest xml file
     *
     * @return void
     * @throws Exception
     */
    public function setManifest($path)
    {
        if (empty($path) || !file_exists(realpath($path))) {
            throw new Exception("Invalid XML file path");
        }

        $this->manifest = $path;
    }

    /**
     * The setter for the attribute "basePath". It should point to
     * the composer.json file
     *
     * @param string $path The path for the basePath xml file
     *
     * @return void
     * @throws Exception
     */
    public function setBasePath($path)
    {
        if (empty($path) || !file_exists(realpath($path))) {
            throw new Exception("Invalid base path");
        }

        $this->basePath = $path;
    }

    /**
     * The setter for the attribute "suffix".
     *
     * @param string $suffix
     *
     * @return void
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
    }

    /**
     * The init method
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * The method that runs the task
     *
     * @return void
     * @throws Exception
     */
    public function main()
    {
        $xml = simplexml_load_file($this->manifest);

        // Single files
        if (!empty($xml->alledia->minify->script)) {
            foreach ($xml->alledia->minify->script as $script) {
                $this->minify($script);
            }
        }

        // Scripts bundle
        if (!empty($xml->alledia->minify->scripts)) {
            /** @var AppendTask $append */
            $append = $this->project->createTask("append");
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
                        $append->setFixlastline(true);
                        $append->setEol('unix');
                        $append->setFile(new PhingFile($this->basePath . '/' . $script));
                        $append->main();
                    }

                    $this->minify($destination);
                }
            }
        }
    }

    /**
     * Minify a file
     *
     * @param  string $file
     *
     * @return void
     * @throws Exception
     */
    protected function minify($file)
    {
        /** @var JsMinTask $minify */
        $minify = $this->project->createTask('jsMin');
        $minify->init();
        $minify->setTargetDir($this->basePath);
        $minify->setFailonerror(true);
        $minify->setSuffix($this->suffix);

        $fileset = new Fileset();
        $fileset->setDir($this->basePath);
        $fileset->setIncludes($file);

        $minify->addFileSet($fileset);
        $minify->main();
    }
}

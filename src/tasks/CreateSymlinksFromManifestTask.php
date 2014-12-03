<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2014 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'library/FileSystemTask.php';

class CreateSymlinksFromManifestTask extends FileSystemTask
{
    /**
     * Manifest file
     *
     * @var array
     */
    protected $manifestFile;

    /**
     * The symlinks destination base path
     *
     * @var string
     */
    protected $destinationBasePath;

    /**
     * The project source base path
     *
     * @var string
     */
    protected $sourceBasePath;

    /**
     * The manifest object
     *
     * @var SimpleXML
     */
    protected $manifest;

    /**
     * Composer data
     *
     * @var stdClass
     */
    protected $composer;

    /**
     * Build properties data from build.properties file
     *
     * @var array
     */
    protected $buildProperties;

    /**
     * The setter for the attribute "mapFile". It should point to
     * a .yml file
     *
     * @param string $path The path for the symlinks map file
     * @return void
     */
    public function setManifestFile($path)
    {
        if (empty($path) || ! file_exists(realpath($path))) {
            throw new Exception("Invalid manifest file", 1);
        }

        $this->manifestFile = $path;
    }

    /**
     * The setter for the attribute sourceBasePath
     *
     * @param string $dir The path for base project dir
     * @return void
     */
    public function setSourceBasePath($path)
    {
        $this->sourceBasePath = $path;
    }

    /**
     * The setter for the attribute destinationBasePath
     *
     * @param string $path The base path of destination
     * @return void
     */
    public function setDestinationBasePath($path)
    {
        $this->destinationBasePath = $path;
    }

    /**
     * The method that runs the task
     *
     * @return void
     */
    public function main()
    {
        parent::main();

        $this->manifest        = simplexml_load_file($this->manifestFile);
        $this->composer        = $this->getComposerData();
        $this->buildProperties = $this->getBuildPropertiesData();

        // Administrator
        if ($this->manifest->administration) {
            $this->createSymlinksFromManifestTag(
                $this->manifest->administration->files,
                'administrator',
                'joomla.component' === $this->composer->type
            );
        }

        // Site
        if ($this->manifest->files) {
            $this->createSymlinksFromManifestTag(
                $this->manifest->files,
                null,
                'joomla.component' !== $this->composer->type
            );
        }

        // Media
        if ($this->manifest->media) {
            $this->createSymlinksFromManifestTag(
                $this->manifest->media,
                'media',
                true
            );
        }
    }

    protected function getComposerData()
    {
        $composerPath = $this->sourceBasePath . '/composer.json';

        if (!file_exists($composerPath)) {
            throw new Exception("Composer file not found: {$composerPath}");
        }

        $content = file_get_contents($composerPath);

        return json_decode($content);
    }

    protected function getBuildPropertiesData()
    {
        // Get the Free extension path from the build.properties file
        $propertiesFile = $this->sourceBasePath . '/build.properties';
        if (!file_exists($propertiesFile)) {
            throw new Exception("File not found: {$propertiesFile}");
        }

        return parse_ini_file($propertiesFile);
    }

    protected function createSymlinksFromManifestTag(
        $tag,
        $destinationBaseFolder = null,
        $includeInstallerAsSource = false
    )
    {
        $license         = (string) $this->manifest->alledia->license;
        $sourcePathArray = $this->getSourcePathArray($tag, $license, $includeInstallerAsSource);
        $destinationPath = $this->getDestinationPath($tag, $destinationBaseFolder);

        // Remove existent folders/files
        if (file_exists($destinationPath)) {
            $this->remove($destinationPath);
        }

        // Folders
        if ($tag->folder) {
            foreach ($tag->folder as $folder) {
                $folder = (string) $folder;
                $path   = $destinationPath . '/' . $folder;

                $this->mkdir($path);

                foreach ($sourcePathArray as $sourcePath) {
                    if ($sourcePath) {
                        $this->createRecursiveSymlinks($sourcePath, $destinationPath, $folder);
                    }
                }
            }
        }

        // Files
        if ($tag->filename) {
            foreach ($tag->filename as $fileName) {
                $fileName    = (string) $fileName;
                $destination = $destinationPath . '/' . $fileName;

                foreach ($sourcePathArray as $sourcePath) {
                    if ($sourcePath) {
                        $source = $sourcePath . '/' . $fileName;

                        if (file_exists($source)) {
                            $this->symlink($source, $destination);
                        }
                    }
                }
            }
        }
    }

    protected function getSourcePathArray($tag, $license, $includeInstallerAsSource = false)
    {
        $sourcePath      = $this->sourceBasePath . '/src';
        $subSourceFolder = (string) $tag->attributes()['folder'];

        if (!empty($subSourceFolder)) {
            $sourcePath .= '/' . $subSourceFolder;
        }

        $sourcePath = realpath($sourcePath);

        // This is an array because we will find the source files following the ordering: Pro, Free, Installation
        $sources = array();

        // Current extension
        $sources[] = $sourcePath;

        // Free code, if Pro
        if ($license === 'pro') {
            $freeProjectPath = $this->buildProperties["project.{$this->manifest->alledia->namespace}.path"] . '/src';

            if (!file_exists($freeProjectPath)) {
                throw new Exception("Path not found: {$freeProjectPath}");
            }

            if (!empty($subSourceFolder)) {
                $freeProjectPath .= '/' . $subSourceFolder;
            }

            $sources[] = $freeProjectPath;
        }

        // Installer
        if ($includeInstallerAsSource) {
            $installerProjectPath = $this->buildProperties["project.AllediaInstaller.path"] . '/src';

            if (!file_exists($installerProjectPath)) {
                throw new Exception("Path not found: {$installerProjectPath}");
            }

            if ('media' === $tag->getName() && !empty($subSourceFolder)) {
                $installerProjectPath .= '/' . $subSourceFolder;
            }

            $sources[] = $installerProjectPath;
        }

        return $sources;
    }

    protected function getDestinationPath($tag, $destinationBaseFolder = '')
    {
        $destinationPath = $this->destinationBasePath;
        $elementShortPropertyName = 'element-short';
        $elementShort    = $this->composer->extra->$elementShortPropertyName;

        if (!empty($destinationBaseFolder)) {
            $destinationPath .= '/' . $destinationBaseFolder;
        }

        if ($destinationBaseFolder !== 'media') {
            // Append folders to the path according to the extension type
            switch ($this->manifest->attributes()['type']) {
                case 'component':
                    $destinationPath .= "/components/com_{$elementShort}";
                    break;

                case 'plugin':
                    $group = $this->manifest->attributes()['group'];
                    $destinationPath .= "/plugins/{$group}/{$elementShort}";
                    break;

                case 'module':
                    $destinationPath .= "/modules/mod_{$elementShort}";

                case 'template':
                    $destinationPath .= "/templates/{$elementShort}";
            }
        }

        $subDestinationFolder = (string) $tag->attributes()['destination'];

        if (!empty($subDestinationFolder)) {
            $destinationPath .= '/' . $subDestinationFolder;
        }

        return $destinationPath;
    }

    protected function createRecursiveSymlinks($sourcePath, $destinationPath, $folder)
    {
        $sourceFolderPath = $sourcePath . '/' . $folder;

        if (file_exists($sourceFolderPath)) {
            $directory = new RecursiveDirectoryIterator($sourceFolderPath);
            $iterator  = new RecursiveIteratorIterator($directory);

            foreach ($iterator as $info) {
                $fileName = $info->getFileName();
                $pathName = $info->getPathName();

                // Ignore the lookback folders
                if ($fileName === '..') {
                    continue;
                }

                $source             = realpath($pathName);
                $sourceRelativePath = str_replace($sourcePath . '/', '', $source);
                $destination        = $destinationPath . '/' . $sourceRelativePath;

                if (is_dir($source)) {
                    if (!file_exists($destination)) {
                        $this->mkdir($destination);
                    }
                } elseif (is_file($source) || is_link($source)) {
                    if (!file_exists($destination)) {
                        $this->symlink($source, $destination);
                    }
                }
            }
        }
    }
}

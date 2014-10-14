<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2014 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
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
     */
    public function setBasePath($path)
    {
        $this->basePath = $path;
    }

    /**
     * Set the pattern to locate the file pair
     *
     * @param string $pattern The pattern
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * Set the string to replace the pattern
     *
     * @param string $replace The string
     */
    public function setReplace($replace = '')
    {
        $this->replace = $replace;
    }

    /**
     * The method that runs the task
     *
     * @return void
     */
    public function main()
    {
        // Locate files on the base path that match the pattern
        $files = scandir($this->basePath);
        $toMerge = array();

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

<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2015 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/tasks/system/CopyTask.php';

/**
 * Copy files but stores a list of the files' checksum
 */
class CopyHashingTask extends CopyTask
{
    /**
     * The path for the file that will store the list
     * @var string
     */
    protected $checksumFile;

    /**
     * [$basePath description]
     * @var string
     */
    protected $basePath;

    /**
     * Set the value for checksumFile
     *
     * @param  stromg $path The file path
     * @return void
     */
    public function setChecksumFile($path)
    {
        $this->checksumFile = $path;
    }

    /**
     * Add a slash to the end of the string, if doesn't have already
     *
     * @param string $string The string
     * @return string
     */
    protected function addTrailingSlash($string)
    {
        if (!preg_match('#/$#', $string)) {
            $string .= '/';
        }

        return $string;
    }

    /**
     * Set the value for basePath. This path allows to print the file path
     * as a relative path.
     *
     * @param string $path The base path to strip from the file being logged
     * @return void
     */
    public function setBasePath($path)
    {
        $this->basePath = $this->addTrailingSlash(realpath($path));
    }

    /**
     * Method executed to copy all the files. After copy it intercepts the file list
     * and calculate the checksum for each file, storing in the specified file.
     *
     * @return void
     */
    protected function doWork()
    {
        parent::doWork();

        $checksumFile = fopen($this->checksumFile, 'a');

        // Run the file map calculating the checksum
        foreach ($this->fileCopyMap as $source => $dest) {
            $relativePath = str_replace($this->addTrailingSlash($this->destDir), '', $dest);
            $sum          = sha1_file($source);

            fwrite($checksumFile, $relativePath . ':' . $sum . "\n");
        }

        fclose($checksumFile);
    }
}

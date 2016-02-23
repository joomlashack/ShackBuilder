<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2016 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'FileSystemTask.php';
require_once 'Spyc.php';

class MappedSymlinksTask extends FileSystemTask
{
    /**
     * Symlinks map
     *
     * @var array
     */
    protected $map;

    /**
     * File with the symlinks map
     *
     * @var string
     */
    protected $mapFile;

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
     * The setter for the attribute "mapFile". It should point to
     * a .yml file
     *
     * @param string $path The path for the symlinks map file
     * @return void
     */
    public function setMapFile($path)
    {
        if (empty($path) || ! file_exists(realpath($path))) {
            throw new Exception("Invalid symlink map file", 1);
        }

        $this->mapFile = $path;
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
     */
    public function main()
    {
        $this->map = Spyc::YAMLLoad($this->mapFile);

        if (!isset($this->map['symlinks'])) {
            throw new Exception("Invalid symlinks map file", 1);
        }
    }
}

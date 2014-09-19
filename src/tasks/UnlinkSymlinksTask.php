<?php
/**
 * @package   AllediaBuilder
 * @contact   www.ostraining.com, support@ostraining.com
 * @copyright 2014 Open Source Training, LLC. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'library/MappedSymlinksTask.php';

class UnlinkSymlinksTask extends MappedSymlinksTask
{
    /**
     * The method that runs the task
     *
     * @return void
     */
    public function main()
    {
        parent::main();

        // Do we need to remove created directories?
        if (isset($this->map['mkdir'])) {
            foreach ($this->map['mkdir'] as $dir) {
                $path = realpath($this->destinationBasePath . '/' . $dir);

                if (file_exists($path)) {
                    $this->remove($path);
                }
            }
        }

        foreach ($this->map['symlinks'] as $item) {
            $source      = array_keys($item)[0];
            $destination = array_values($item)[0];

            // Normalise paths
            $source      = realpath($this->sourceBasePath . '/' . $source);
            $destination = rtrim($this->destinationBasePath, '/') . '/' . $destination;

            if (!file_exists($source)) {
                throw new Exception("Symlink target not found: " . $this->sourceBasePath . '/' . $source, 1);
            }

            // Check if the destination exists and remove it
            if (file_exists($destination)) {
                $destination = rtrim($destination, '/');

                $this->remove($destination);
            }
        }
    }
}
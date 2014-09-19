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
        if (!isset($this->map['symlinks'])) {
            throw new Exception("Invalid symlinks map file", 1);
        }

        // Do we need to remove created directories?
        if (isset($this->map['mkdir'])) {
            foreach ($this->map['mkdir'] as $dir) {
                $path = realpath($this->basepath . '/' . $dir);

                if (file_exists($path)) {
                    $this->remove($path);
                }
            }
        }

        foreach ($this->map['symlinks'] as $item) {
            $source      = array_keys($item)[0];
            $destination = array_values($item)[0];

            // Normalise paths
            $sourcePath      = realpath($this->phingdir . '/../' . $source);
            $destinationPath = rtrim($this->basepath, '/') . '/' . $destination;

            if (!file_exists($sourcePath)) {
                throw new Exception("Symlink target not found: " . $this->phingdir . '/../' . $source, 1);
            }

            // Check if the destination exists and remove it
            if (file_exists($destinationPath)) {
                $destinationPath = rtrim($destinationPath, '/');

                $this->remove($destinationPath);
            }
        }
    }
}

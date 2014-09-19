<?php
/**
 * @package   AllediaBuilder
 * @contact   www.ostraining.com, support@ostraining.com
 * @copyright 2014 Open Source Training, LLC. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';

class FileSystemTask extends Task
{
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
    }

    /**
     * Method to remothe file, symlink or directories (recursively)
     *
     * @param  string $path The path for what you wan't to remove
     * @return bool
     */
    protected function remove($path)
    {
        if (is_link($path) || is_file($path)) {
            $path = rtrim($path, '/');
            $this->log('Removing ' . (is_link($path) ? 'symlink' : 'file') . ': ' . $path);
            unlink($path);
        } elseif (is_dir($path)) {
            // Remove all child files and directories
            $items = array_diff(scandir($path), array('.','..'));

            foreach ($items as $item) {
                $item = $path . '/' . $item;
                $this->remove($item);
            }

            // Remove the empty directory
            $this->log('Removing directory: ' . $path);
            $result = rmdir($path);

            return $result;
        }

        return false;
    }
}

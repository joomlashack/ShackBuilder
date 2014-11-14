<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2014 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';
require_once __DIR__ . '/GitCommandTask.php';

class GitStatusTask extends GitCommandTask
{
    /**
     * The method that runs the task
     *
     * @return void
     */
    public function main()
    {
        $this->log('Checking ' . $this->path, Project::MSG_VERBOSE);

        // Check if we have any non committed change
        $output = $this->git('status');
        if (! substr_count($output, 'working directory clean') > 0) {
            $this->log($output, Project::MSG_WARN);
            $this->log("{$this->projectName}'s dirty. Please, commit all changes before release", Project::MSG_WARN);
            throw new BuildException($this->projectName . "'s repository is dirty. Please, commit all changes before release");
        }

        $this->log($this->projectName . "'s repository is clean");
    }
}

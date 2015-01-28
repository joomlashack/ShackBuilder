<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2015 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';
require_once __DIR__ . '/GitCommandTask.php';

class GitFetchAllTask extends GitCommandTask
{
    /**
     * The method that runs the task
     *
     * @return void
     */
    public function main()
    {
        $this->log('Fetching ' . $this->path, Project::MSG_VERBOSE);

        $output = $this->git('fetch --all');
        $this->log($output, Project::MSG_DEBUG);
    }
}

<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2015 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';

class GitCommandTask extends Task
{
    /**
     * The project path to check
     *
     * @var array
     */
    protected $path;

    /**
     * The project name
     *
     * @var array
     */
    protected $projectName;

    /**
     * Set the project path
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Set the project name
     *
     * @param string $project
     */
    public function setProjectName($projectName)
    {
        $this->projectName = $projectName;
    }

    /**
     * Run Git commands inside the path
     *
     * @param  string $command
     * @return string
     */
    protected function git($command)
    {
        return shell_exec('cd ' . $this->path . '; git ' . $command);
    }

    /**
     * The method that runs the task
     *
     * @return void
     */
    public function main()
    {

    }
}

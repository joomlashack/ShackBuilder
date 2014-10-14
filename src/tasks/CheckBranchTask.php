<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2014 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';

class CheckBranchTask extends Task
{
    /**
     * Projects path, where we will look for projects
     *
     * @var string
     */
    protected $projectsPath;

    /**
     * The project list to check, as CSV
     *
     * @var array
     */
    protected $projects;

    /**
     * Set the project path
     *
     * @param string $path
     */
    public function setProjectPath($path)
    {
        $this->projectsPath = $path;
    }

    /**
     * Set the project list, converting the string to array
     *
     * @param string $projects The project list as CSV
     */
    public function setProjects($projects)
    {
        $this->projects = explode(',', $projects);
    }

    /**
     * The method that runs the task
     *
     * @return void
     */
    public function main()
    {
        $hasNonMaster = false;
        $offset = str_repeat(' ', 14);
        $output = "=================[ Branches ]=================\n\n";

        if (isset($this->projects)) {
            foreach ($this->projects as $project) {
                $headFilePath = realpath($this->projectsPath . '/' . $project . '/.git/HEAD');

                if (is_file($headFilePath)) {
                    $head = file_get_contents($headFilePath);

                    if (!empty($head)) {
                        preg_match('/heads\/(.*)/', $head, $matchs);
                        $branch = $matchs[1];

                        $output .= $offset . str_pad($project, 20, ' ') . ': ' . $branch;

                        if ($branch !== 'master') {
                            $output .= ' (*)';
                            $hasNonMaster = true;
                        }

                        $output .= "\n";
                    }
                }
            }
        }

        if ($hasNonMaster) {
            $output .= "\n" . $offset . "(*) Non master repositories\n";
        }

        $output .= $offset . "=============================================";

        $this->log($output);
    }
}

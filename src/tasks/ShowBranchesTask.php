<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2015 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';

class ShowBranchesTask extends Task
{
    /**
     * The project list to check, as CSV
     *
     * @var array
     */
    protected $projects;

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
        $properties = $this->project->getProperties();

        // Get the projects path
        $paths = array();
        foreach ($properties as $property => $value) {
            if (preg_match('/^project\.([a-z0-9_\-]+)\.path/i', $property, $matches)) {
                $paths[$matches[1]] = $value;
            }
        }

        $hasNonMaster = false;
        $offset = str_repeat(' ', 15);
        $output = "=================[ Branches ]================\n\n";

        $longestLength = 0;

        if (isset($this->projects)) {

            // Get the longest project name length
            foreach ($this->projects as $project) {
                $length = strlen($project);
                if ($length > $longestLength) {
                    $longestLength = $length;
                }
            }

            // Iteration into the projects
            foreach ($this->projects as $project) {
                if (isset($paths[$project])) {
                    $headFilePath = realpath($paths[$project] . '/.git/HEAD');

                    if (is_file($headFilePath)) {
                        $head = file_get_contents($headFilePath);

                        if (!empty($head)) {
                            preg_match('/heads\/(.*)/', $head, $matches);
                            $branch = $matches[1];

                            $output .= $offset . str_pad($project, $longestLength, ' ') . ': ' . $branch;

                            if ($branch !== 'master') {
                                $output .= ' (*)';
                                $hasNonMaster = true;
                            }

                            $output .= "\n";
                        }
                    }
                }
            }
        }

        if ($hasNonMaster) {
            $output .= "\n" . $offset . "(*) Non master repositories\n";
        }

        $output .= $offset . "=============================================";

        if ($hasNonMaster) {
            $this->log($output, Project::MSG_WARN);
            $this->log('Are you sure you want non-master branches?', Project::MSG_WARN);
        } else {
            $this->log($output);
        }
    }
}

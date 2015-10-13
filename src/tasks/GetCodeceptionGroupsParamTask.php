<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2015 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';

class GetCodeceptionGroupsParamTask extends Task
{
    /**
     * The groups list to build the param
     *
     * @var array
     */
    protected $groups;

    /**
     * The property to set
     *
     * @var array
     */
    protected $property;

    /**
     * Set the groups list, converting the string to array
     *
     * @param string $groups The groups list
     */
    public function setGroups($groups)
    {
        $this->groups = explode(',', $groups);
    }

    /**
     * Set the property name to the result
     *
     * @param string $property The property name
     */
    public function setProperty($property)
    {
        $this->property = (string) $property;
    }

    /**
     * The method that runs the task
     *
     * @return void
     */
    public function main()
    {
        $value = '';
        foreach ($this->groups as $group) {
            $value .= ' -g ' . $group;
        }

        $this->project->setProperty($this->property, $value);
    }
}

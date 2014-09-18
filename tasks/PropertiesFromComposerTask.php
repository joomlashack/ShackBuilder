<?php
/**
 * @package   AllediaBuilder
 * @contact   www.ostraining.com, support@ostraining.com
 * @copyright 2014 Open Source Training, LLC. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';

class PropertiesFromComposerTask extends Task
{
    /**
     * JSON file with the properties
     *
     * @var string
     */
    protected $file;

    /**
     * The setter for the attribute "file". It should point to
     * the composer.json file
     *
     * @param string $path The path for the composer.json file
     * @return void
     */
    public function setFile($path)
    {
        if (empty($path) || ! file_exists(realpath($path))) {
            throw new Exception("Invalid composer.json file path", 1);
        }

        $this->file = $path;
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
        $json = json_decode(file_get_contents($this->file), true);

        $self = $this;
        $setProperties = function (&$propertyName, $propertyValue) use (&$setProperties, &$self) {
            if (is_array($propertyValue)) {
                foreach ($propertyValue as $name => $value) {
                    $name = $propertyName . '.' . $name;

                    $setProperties($name, $value);
                }

            } else {
                $self->project->setProperty($propertyName, $propertyValue);
            }
        };

        $name = 'composer';
        $setProperties($name, $json);
    }
}

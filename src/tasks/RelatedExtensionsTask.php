<?php
/**
 * @package   AllediaBuilder
 * @contact   www.ostraining.com, support@ostraining.com
 * @copyright 2014 Open Source Training, LLC. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';

class RelatedExtensionsTask extends Task
{
    /**
     * XML file with the a tag related extensions
     *
     * @var string
     */
    protected $file;

    /**
     * The property name with the result
     *
     * @var string
     */
    protected $property;

    /**
     * The setter for the attribute "file". It should point to
     * the composer.json file
     *
     * @param string $path The path for the manifest xml file
     * @return void
     */
    public function setFile($path)
    {
        if (empty($path) || ! file_exists(realpath($path))) {
            throw new Exception("Invalid XML file path");
        }

        $this->file = $path;
    }

    /**
     * The setter for the attribute "property"
     *
     * @param string $property The property to receive the result
     * @return void
     */
    public function setProperty($property)
    {
        $this->property = $property;
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
        $xml = simplexml_load_file($this->file);
        $extensions = array();

        foreach ($xml->relatedExtensions->extension as $extension) {
            $extensions[] = (string)$extension;
        }

        $extensions = implode(',', $extensions);
        $this->project->setProperty($this->property, $extensions);

        $this->log('Loaded related extensions into properties');
    }
}

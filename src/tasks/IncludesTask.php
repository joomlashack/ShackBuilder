<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2015 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';

class IncludesTask extends Task
{
    /**
     * XML Manifest file
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
     * The CSV list of includes to ignore, since they were
     * build by the parent project, in case this include
     * is inside another related project. Avoid recursive build.
     *
     * @var string
     */
    protected $ignoreIncludes;

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
     * The setter for the attribute "ignoreIncludes"
     *
     * @param string $ignoreIncludes The CSV list of includes to ignore
     * @return void
     */
    public function setIgnoreIncludes($ignoreIncludes)
    {
        $this->ignoreIncludes = $ignoreIncludes;
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
        $includes = array();

        if (!empty($xml->alledia->include)) {
            foreach ($xml->alledia->include as $include) {
                $includes[] = (string)$include;

                $attributes = $include->attributes();
                foreach ($attributes as $key => $value) {
                    $value = (string)$value;

                    if (!empty($value)) {
                        $this->project->setProperty($this->property . '.' . (string)$include . '.' . $key, $value);
                    }
                }
            }

            $ignore = array();
            if (strpos($this->ignoreIncludes, '$') === 0) {
                $this->ignoreIncludes = '';
            }

            if (!empty($this->ignoreIncludes)) {
                $ignore = explode(',', $this->ignoreIncludes);
            }

            if (!empty($ignore)) {
                $this->log('Ignored some includes: ' . $this->ignoreIncludes);
            }

            // Strip the extensions we should ignore
            $includes = array_diff($includes, $ignore);

            // Store on the property
            $includes = implode(',', $includes);
            $this->project->setProperty($this->property, $includes);

            $this->log('Loaded includes into properties');
        }
    }
}

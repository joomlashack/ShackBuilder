<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2014 Alledia.com, All rights reserved
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
     * The CSV list of extensions to ignore, since they were
     * build by the parent extension, in case this extension
     * is related inside other extension. Avoid recursive build.
     *
     * @var string
     */
    protected $ignoreRelatedExtensions;

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
     * The setter for the attribute "ignoreRelatedExtensions"
     *
     * @param string $ignoreRelatedExtensions The CSV list of extensions to ignore
     * @return void
     */
    public function setIgnoreRelatedExtensions($ignoreRelatedExtensions)
    {
        $this->ignoreRelatedExtensions = $ignoreRelatedExtensions;
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

        if (!empty($xml->alledia->relatedExtensions)) {
            foreach ($xml->alledia->relatedExtensions->extension as $extension) {
                $extensions[] = (string)$extension;
            }

            $ignore = array();
            if (strpos($this->ignoreRelatedExtensions, '$') === 0) {
                $this->ignoreRelatedExtensions = '';
            }

            if (!empty($this->ignoreRelatedExtensions)) {
                $ignore = explode(',', $this->ignoreRelatedExtensions);
            }

            if (!empty($ignore)) {
                $this->log('Ignored some related extensions: ' . $this->ignoreRelatedExtensions);
            }

            // Strip the extensions we should ignore
            $extensions = array_diff($extensions, $ignore);

            // Store on the property
            $extensions = implode(',', $extensions);
            $this->project->setProperty($this->property, $extensions);

            $this->log('Loaded related extensions into properties');
        }
    }
}

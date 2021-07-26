<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2016 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';
require_once 'TraitShack.php';

class PropertiesFromComposerTask extends Task
{
    use TraitShack;

    /**
     * JSON file with the properties
     *
     * @var string
     */
    protected $file;

    /**
     * Property prefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * @param ?string $path
     *
     * @return void
     */
    public function setFile(?string $path)
    {
        if (empty($path) || !file_exists(realpath($path))) {
            $this->throwError('Invalid composer.json file path');
        }

        $this->file = $path;
    }

    /**
     * Set the property prefix
     *
     * @param [type] $prefix [description]
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
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
     * @inheritDoc
     */
    public function main()
    {
        if (empty($this->prefix)) {
            $this->prefix = 'composer';
        }

        $json = json_decode(file_get_contents($this->file), true);

        // Verify project.type
        if (array_key_exists('type', $json)) {
            if (!array_key_exists($json['type'], $this->types)) {
                $this->throwError('Invalid Joomla Extension Type: ' . $json['type']);
            }
        } else {
            $this->throwError('Missing composer property: Unable to determine Joomla type');
        }

        $name = $this->prefix;
        $this->setProperties($name, $json);

        $this->project->setProperty('project.type', $this->types[$json['type']]);

        $this->log('Loaded composer.json data into properties');
    }
}

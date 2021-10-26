<?php
/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2016-2021 Joomlashack.com. All rights reserved
 * @license   https://www.gnu.org/licenses/gpl.html GNU/GPL
 *
 * This file is part of ShackBuilder.
 *
 * ShackBuilder is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * ShackBuilder is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ShackBuilder.  If not, see <https://www.gnu.org/licenses/>.
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

        $fileText = file_get_contents($this->file);
        $json     = json_decode($fileText, true);

        // Verify project.type
        if (array_key_exists('type', $json)) {
            $type = str_replace('.', '-', $json['type']);
            if ($type != $json['type']) {
                file_put_contents($this->file, str_replace($json['type'], $type, $fileText));
                $json['type'] = $type;
                $this->log('composer.json has been updated, be sure to review the changes', Project::MSG_WARN);
            }

            if (str_replace('.', '-', $json[''])) {
                if (!array_key_exists($json['type'], $this->types)) {
                    $this->throwError('Invalid Joomla Extension Type: ' . $json['type']);
                }
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

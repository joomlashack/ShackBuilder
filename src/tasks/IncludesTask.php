<?php

/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2016-2026 Joomlashack.com. All rights reserved
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

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require_once 'phing/Task.php';
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

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
     *
     * @return void
     * @throws Exception
     */
    public function setFile($path)
    {
        if (empty($path) || !file_exists(realpath($path))) {
            throw new Exception('Invalid XML file path');
        }

        $this->file = $path;
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
        $xml      = simplexml_load_file($this->file);
        $includes = [];

        if (!empty($xml->alledia->include)) {
            foreach ($xml->alledia->include as $include) {
                $includes[] = (string)$include;

                $attributes = $include->attributes();
                foreach ($attributes as $key => $value) {
                    $value = (string)$value;

                    if (!empty($value)) {
                        $this->project->setProperty($this->property . '.' . $include . '.' . $key, $value);
                    }
                }
            }

            $ignore = [];
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
}

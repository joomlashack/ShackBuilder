<?php

/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2016-2025 Joomlashack.com. All rights reserved
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


class RelatedExtensionsTask extends Task
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
        $xml        = simplexml_load_file($this->file);
        $extensions = [];

        if (!empty($xml->alledia->relatedExtensions)) {
            foreach ($xml->alledia->relatedExtensions->extension as $extension) {
                $extensionName = (string)$extension;
                $extensions[]  = $extensionName;

                // Store in a property the element of each related extensions
                $this->project->setProperty(
                    $this->property . '.' . $extensionName . '.element',
                    (string)$extension->attributes()->element
                );

                // Store in a property the flag to set update the extension's version on build
                $this->project->setProperty(
                    $this->property . '.' . $extensionName . '.updateversion',
                    (string)$extension->attributes()->updateversion
                );
            }

            $ignore = [];
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

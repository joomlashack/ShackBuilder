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
require_once 'TraitShack.php';
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/**
 * SetversionTask
 *
 * Increments a three-part version number from a given Joomla Manifest file
 * and writes it back to the file.
 * Incrementing is based on given releasetype, which can be one
 * of Major, Minor and Bugfix.
 * Resulting version number is also published under supplied property.
 * Based on original VersionTask.php
 */
class SetversionTask extends Task
{
    use TraitShack;

    /**
     * @var string $releasetype
     */
    protected $releasetype;

    /**
     * @var PhingFile file
     */
    protected $file;

    /**
     * @var string $property
     */
    protected $property;

    /**
     * @var string $customValue
     */
    protected $customValue;

    /* Regex to match for version number */
    public const REGEX = '#(<version>\s*)(\d*)\.?(\d*)\.?(\d*)(?:(a|b|rc)?(\d*))([^<]*)?(</version>)#m';

    /* Allowed Release types */
    public const RELEASETYPE_MAJOR  = 'MAJOR';
    public const RELEASETYPE_MINOR  = 'MINOR';
    public const RELEASETYPE_BUGFIX = 'BUGFIX';
    public const RELEASETYPE_ALPHA  = 'A';
    public const RELEASETYPE_BETA   = 'B';
    public const RELEASETYPE_RC     = 'RC';
    public const RELEASETYPE_CUSTOM = 'CUSTOM';
    public const RELEASETYPE_STABLE = 'STABLE';

    /**
     * Set Property for Releasetype (Minor, Major, Bugfix)
     *
     * @param string $releasetype
     *
     * @return void
     */
    public function setReleasetype(string $releasetype)
    {
        $this->releasetype = strtoupper($releasetype);
    }

    /**
     * Set Property for File containing versioninformation
     *
     * @param PhingFile $file
     *
     * @return void
     */
    public function setFile(PhingFile $file)
    {
        $this->file = $file;
    }

    /**
     * Set name of property to be set
     *
     * @param string $name
     *
     * @return void
     */
    public function setProperty(string $name)
    {
        $this->property = $name;
    }

    /**
     * Set custom value for version
     *
     * @param string $customValue
     *
     * @return void
     */
    public function setCustomvalue(string $customValue)
    {
        $this->customValue = $customValue;
    }

    /**
     * Main-Method for the Task
     *
     * @return  void
     * @throws  BuildException
     */
    public function main()
    {
        // check supplied attributes
        $this->checkReleasetype();
        $this->checkFile();
        $this->checkProperty();

        // read file
        $fileContent = trim(file_get_contents($this->file));

        // get new version
        $newVersion = $this->getVersion($fileContent);

        // Update the file
        $this->updateFile($newVersion);

        // publish new version number as property
        $this->project->setProperty($this->property, $newVersion);
    }


    /**
     * Returns new version number corresponding to Release type
     *
     * @param string $fileContent
     *
     * @return string
     */
    protected function getVersion(string $fileContent): string
    {
        // init
        $newVersion = '';

        // Extract version
        preg_match(self::REGEX, $fileContent, $match);
        list(, , $major, $minor, $bugfix, $buildType, $build, $sufix,) = $match;

        // Return new version number
        switch ($this->releasetype) {
            case self::RELEASETYPE_MAJOR:
                $newVersion = sprintf(
                    '%d.%d.%d',
                    ++$major,
                    0,
                    0
                );

                $build = null;
                break;

            case self::RELEASETYPE_MINOR:
                $newVersion = sprintf(
                    '%d.%d.%d',
                    $major,
                    ++$minor,
                    0
                );

                $build = null;
                break;

            case self::RELEASETYPE_BUGFIX:
                $newVersion = sprintf(
                    '%d.%d.%d',
                    $major,
                    $minor,
                    ++$bugfix
                );

                $build = null;
                break;

            case self::RELEASETYPE_ALPHA:
            case self::RELEASETYPE_BETA:
            case self::RELEASETYPE_RC:
                $newVersion = sprintf(
                    '%d.%d.%d',
                    $major,
                    $minor,
                    $bugfix
                );

                if (empty($build)) {
                    $build = 0;
                }

                // Reset the build type if asked for a new one
                if (strtolower($buildType) !== strtolower($this->releasetype)) {
                    $buildType = $this->releasetype;
                    $build     = 0;
                }

                $build++;
                break;

            case self::RELEASETYPE_CUSTOM:
                $newVersion = $this->customValue;

                $build = null;
                break;

            case self::RELEASETYPE_STABLE:
                $newVersion = sprintf(
                    '%d.%d.%d',
                    $major,
                    $minor,
                    $bugfix
                );

                $build = null;
                break;
        }

        if (!empty($build)) {
            $newVersion .= strtolower($buildType) . "{$build}";
        }

        return $newVersion . $sufix;
    }


    /**
     * checks releasetype attribute
     *
     * @return void
     * @throws BuildException
     */
    protected function checkReleasetype()
    {
        // check Releasetype
        if (is_null($this->releasetype)) {
            $this->throwError('releasetype attribute is required', $this->location);
        }

        // known release types
        $releaseTypes = [
            self::RELEASETYPE_MAJOR,
            self::RELEASETYPE_MINOR,
            self::RELEASETYPE_BUGFIX,
            self::RELEASETYPE_ALPHA,
            self::RELEASETYPE_BETA,
            self::RELEASETYPE_RC,
            self::RELEASETYPE_CUSTOM,
            self::RELEASETYPE_STABLE,
        ];

        if (in_array($this->releasetype, $releaseTypes) == false) {
            $this->throwError(
                sprintf(
                    'Unknown Releasetype %s..Must be one of Major, Minor or Bugfix, Stable',
                    $this->releasetype
                ),
                $this->location
            );
        }
    }

    /**
     * checks file attribute
     *
     * @return void
     * @throws BuildException
     */
    protected function checkFile()
    {
        // check File
        if ($this->file === null || strlen($this->file) == 0) {
            $this->throwError('You must specify a Joomla manifest file', $this->location);
        }

        $content = file_get_contents($this->file);
        if (strlen($content) == 0) {
            $this->throwError(sprintf('Supplied file %s is empty', $this->file), $this->location);
        }

        // check for xml version tag
        if (!preg_match(self::REGEX, $content)) {
            $this->throwError('Unable to find version tag', $this->location);
        }
    }

    /**
     * checks property attribute
     *
     * @return void
     * @throws BuildException
     */
    protected function checkProperty()
    {
        if (is_null($this->property) || strlen($this->property) === 0) {
            $this->throwError('Property for publishing version number is not set', $this->location);
        }

        if ($this->releasetype === self::RELEASETYPE_CUSTOM && is_null($this->customValue)) {
            $this->throwError('Property for custom value is not set: ' .  $this->customValue, $this->location);
        }
    }

    /**
     * @param string $newVersion
     *
     * @return void
     */
    protected function updateFile(string $newVersion)
    {
        $content = file_get_contents($this->file);

        if (preg_match(self::REGEX, $content, $match)) {
            $source  = array_shift($match);
            $head    = array_shift($match);
            $tail    = array_pop($match);
            $replace = $head . $newVersion . $tail;


            $content = str_replace($source, $replace, $content);
            file_put_contents($this->file, $content);

            $this->log('Updated ' . basename($this->file) . ' to new version: ' . $newVersion);
        }
    }
}

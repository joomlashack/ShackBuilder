<?php

/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2020-2025 Joomlashack.com. All rights reserved
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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

trait TraitShack
{
    protected $types = [
        'joomla-plugin'    => 'plg',
        'joomla-module'    => 'mod',
        'joomla-template'  => 'tpl',
        'joomla-component' => 'com',
        'joomla-package'   => 'pkg',
        'joomla-file'      => 'file',
        'joomla-cli'       => 'cli',
        'joomla-library'   => 'lib'
    ];

    /**
     * @var string[]
     */
    protected $extensionTypes = [
        'component' => 'com',
        'plugin'    => 'plg',
        'library'   => 'lib',
        'file'      => 'file',
        'module'    => 'mod',
        'package'   => 'pkg',
        'template'  => 'tpl'
    ];

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getProperty(string $name, $default = null)
    {
        $value = $this->project->getProperty($name);
        if ($value === null && $default !== null) {
            $value = $this->project->getProperty($default) ?: $default;
        }

        return $value;
    }

    /**
     * @param string  $name
     * @param ?string $value
     *
     * @return ?string
     */
    protected function setProperty(string $name, ?string $value): ?string
    {
        $previous = $this->project->getProperty($name);

        $this->project->setProperty($name, $value);

        return $previous;
    }

    /**
     * @param string          $propertyName
     * @param mixed           $propertyValue
     * @param string|string[] $ignoreProperties
     *
     * @return void
     */
    protected function setProperties(string $propertyName, $propertyValue, $ignoreProperties = [])
    {
        if (is_array($propertyValue)) {
            if ($ignoreProperties && is_string($ignoreProperties)) {
                $ignoreProperties = [$ignoreProperties];
            }

            if (array_search($propertyName, $ignoreProperties) === false) {
                foreach ($propertyValue as $name => $value) {
                    $name = $propertyName . '.' . $name;

                    if (array_search($name, $ignoreProperties) === false) {
                        $this->setProperties($name, $value, $ignoreProperties);
                    }
                }
            }

        } else {
            $this->setProperty($propertyName, $propertyValue);
        }
    }

    /**
     * Execution stops on call
     *
     * @param string|string[] $messages
     * @param ?Location       $location
     *
     * @return void
     */
    protected function throwError($messages, ?Location $location = null)
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $message) {
            $this->log($message, Project::MSG_ERR);
        }

        if ($location) {
            $this->log($location->toString(), Project::MSG_ERR);
        }
        die;
    }

    /**
     * try/catch wrapper for XML processing
     *
     * @param callable $function
     *
     * @return mixed
     */
    protected function tryXmlFunctions(callable $function)
    {
        if (is_callable($function)) {
            $useErrors = libxml_use_internal_errors(true);
            $result    = $function();
            $xmlErrors = libxml_get_errors();
            libxml_use_internal_errors($useErrors);

            if ($xmlErrors) {
                $files    = [];
                $messages = [];
                foreach ($xmlErrors as $error) {
                    $files[]    = $error->file;
                    $messages[] = trim($error->message);
                }
                $files = array_filter(array_unique($files));
                foreach ($files as $file) {
                    $this->log($file, Project::MSG_ERR);
                }
                $this->throwError($messages);
                die;
            }

        } else {
            $this->throwError('Argument is not callable. Type=' . gettype($function));
            die;
        }

        return empty($result) ? null : $result;
    }

    /**
     * @param string $type    Type code or full element name
     * @param string $element Short element
     * @param string $path    Optional base path
     *
     * @return string
     */
    protected function findManifestFile(string $type, string $element, string $path): string
    {
        if (!is_dir($path)) {
            $this->throwError('Invalid Manifest path: ' . $path);
        }

        if (empty($this->extensionTypes[$type])) {
            $this->throwError('Extension type not recognized: ' . $type);
        }

        switch ($type) {
            case 'component':
                if (strpos($element, 'com_') === 0) {
                    $baseName = '/' . substr($element, 4);
                } else {
                    $baseName = '/' . $element;
                }

                break;

            case 'module':
                if (strpos($element, 'mod_') !== 0) {
                    $baseName = '/mod_' . $element;
                }
                break;

            case 'template':
                $baseName = '/templateDetails';
                break;

            default:
                $baseName = '/' . $element;
                break;
        }

        $manifestPath = $path . $baseName . '.xml';
        if (!is_file($manifestPath)) {
            $this->throwError('Manifest not found: ' . $manifestPath);
        }

        return $manifestPath;
    }

    /**
     * @param string[] $strings
     * @param string   $subject
     *
     * @return string[]
     */
    protected function findRegexStrings(array $strings, string $subject): array
    {
        $delimiter = '#';

        $regex = addcslashes(sprintf('\s*(?:%s)', join('|', $strings)), $delimiter);
        if (preg_match_all($delimiter . $regex . $delimiter, $subject, $matches)) {
            return $matches[0];
        }

        return [];
    }

    /**
     * Initialize another task in the project
     *
     * @param string $taskName
     *
     * @return Task
     */
    protected function initProjectTask(string $taskName): Task
    {
        if ($task = $this->project->createTask($taskName)) {
            $task->setOwningTarget($this->getOwningTarget());
            $task->setTaskName($this->getTaskName());
            $task->setLocation($this->getLocation());
            $task->init();

            return $task;
        }

        $this->throwError('Unable to initialize task: ' . $taskName);
        die();
    }
}

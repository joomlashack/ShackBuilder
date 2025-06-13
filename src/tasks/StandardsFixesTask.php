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

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require_once 'phing/Task.php';
require_once 'TraitShack.php';
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class StandardsFixesTask extends Task
{
    use TraitShack;

    /**
     * @var string
     */
    protected $manifest = null;

    /**
     * Preferred order of manifest headers
     *
     * @var string[]
     */
    protected $manifestHeaders = [
        'name'         => null,
        'libraryname'  => null,
        'author'       => '<author>Joomlashack</author>',
        'authorEmail'  => '<authorEmail>help@joomlashack.com</authorEmail>',
        'authorUrl'    => '<authorUrl>https://www.joomlashack.com/</authorUrl>',
        'copyright'    => null,
        'license'      => '<license>GNU GPL; see LICENSE file</license>',
        'description'  => null,
        'version'      => null,
        'creationDate' => null,
        'variant'      => null
    ];

    /**
     * @param string $path
     *
     * @return void
     */
    public function setManifest(string $path)
    {
        $this->manifest = $path;
    }

    /**
     * @inheritDoc
     */
    public function main()
    {
        if (is_file($this->manifest) == false) {
            $this->throwError('Manifest not found: ' . $this->manifest);
        }

        $this->updateManifest($this->manifest);
        $this->updateRelatedManifests($this->manifest);

        $composerPath = $this->getProperty('project.path') . '/composer.json';
        if (is_file($composerPath)) {
            $this->updateComposer($composerPath);

        } else {
            $this->throwError('composer.json not found: ' . $composerPath);
        }
    }

    /**
     * @param string $composerPath
     *
     * @return void
     */
    protected function updateComposer(string $composerPath)
    {
        $composer = file_get_contents($composerPath);
        $sha1     = sha1($composer);

        if (preg_match('#"type"\s*:\s*"(.*)"#', $composer, $match)) {
            // no dots in type
            $fixed    = str_replace('.', '-', $match[0]);
            $composer = str_replace($match[0], $fixed, $composer);
        }
        if (preg_match('#"license"\s*:\s*"(.*)"#', $composer, $match)) {
            // Standardize the license notice
            $fixed    = str_replace($match[1], 'GPL-2.0-or-later', $match[0]);
            $composer = str_replace($match[0], $fixed, $composer);
        }
        if (preg_match('#"php"\s*:\s*"(.*)"#', $composer, $match)) {
            // Update minimum php version
            $fixed    = str_replace($match[1], '>=7.2.5', $match[0]);
            $composer = str_replace($match[0], $fixed, $composer);
        }
        if (preg_match('#"target-platform"\s*:\s*"(.*)"#', $composer, $match)) {
            // Update for Joomla 3 & 4 compatibility
            $fixed    = str_replace($match[1], '.*', $match[0]);
            $composer = str_replace($match[0], $fixed, $composer);
        }

        if (preg_match('#"name"\s*:\s*"(.*)"#', $composer, $match)) {
            $fixed    = str_replace($match[1], strtolower($match[1]), $match[0]);
            $composer = str_replace($match[0], $fixed, $composer);
        }

        if ($sha1 != sha1($composer)) {
            file_put_contents($composerPath, $composer);
            $this->log(
                basename($composerPath) . ' has been updated. Be sure to review the changes.',
                Project::MSG_WARN
            );
        }
    }

    /**
     * @param string $manifestPath
     *
     * @return void
     */
    protected function updateManifest(string $manifestPath)
    {
        $manifestString = file_get_contents($manifestPath);
        $sha1           = sha1($manifestString);

        // Remove obsolete version attribute
        if (preg_match('/<extension.*?(\\s+version.*?\\s+).*/', $manifestString, $version)) {
            $manifestString = str_replace($version[1], ' ', $manifestString);
        }

        // Remove unused tags
        $obsoleteTags = [
            'packager',
            'packagerurl'
        ];
        $obsoleteTags = array_map(
            function ($tag) {
                return sprintf('<%1$s>.*</%1$s>', $tag);
            },
            $obsoleteTags
        );

        $obsoleteMatches = $this->findRegexStrings($obsoleteTags, $manifestString);
        if ($obsoleteMatches) {
            $manifestString = str_replace($obsoleteMatches, '', $manifestString);
        }

        preg_match('/<extension.*>/', $manifestString, $extension);
        $headerStart = strpos($manifestString, $extension[0]) + strlen($extension[0]);

        // Set some headers to our current standards and reorder if needed
        $divider = "\n" . str_repeat(' ', 4);

        $manifestHeaders = $this->manifestHeaders;
        foreach ($manifestHeaders as $header => &$value) {
            $regex = sprintf('#(\s+)<%1$s>.*</%1$s>#', $header);
            if (preg_match($regex, $manifestString, $match)) {
                if (
                    in_array($header, ['creationDate', 'version'])
                    && $this->manifest == $manifestPath
                ) {
                    // Set defaults for these when updating the primary extension
                    $this->manifestHeaders[$header] = trim($match[0]);
                }

                $divider = $match[1];
                if ($value === null) {
                    // No default, copy to replacement string as-is
                    $value = $divider . trim($match[0]);

                } else {
                    // Force to default
                    $value = $divider . $value;
                }

                $manifestString = str_replace($match[0], '', $manifestString);

            } elseif ($value) {
                // Force to default
                $value = $divider . $value;

            } elseif (in_array($header, ['name', 'description', 'copyright'])) {
                // Require these
                if ($header == 'copyright') {
                    // Replicate copyright from primary manifest
                    $mainManifest = $this->tryXmlFunctions(function () {
                        return simplexml_load_file($this->manifest);
                    });

                    $value = (string)$mainManifest->copyright;
                }
                $value = sprintf($divider . '<%1$s>%2$s</%1$s>', $header, $value ?? '');
            }
        }

        $manifestString = substr_replace(
            $manifestString,
            join('', $manifestHeaders),
            $headerStart,
            0
        );

        if ($sha1 != sha1($manifestString)) {
            file_put_contents($manifestPath, $manifestString);

            $basePath = str_replace($this->getProperty('project.path') . '/', '', $manifestPath);
            $this->log(
                $basePath . ' manifest has been updated. Be sure to review the changes',
                Project::MSG_WARN
            );
        }
    }

    /**
     * @param string $manifestPath
     *
     * @return void
     */
    protected function updateRelatedManifests(string $manifestPath)
    {
        /** @var SimpleXMLElement $manifest */
        $manifest = $this->tryXmlFunctions(function () use ($manifestPath) {
            return simplexml_load_file($manifestPath);
        });

        $basePath = $this->getProperty('project.source.path');

        $relatedExtensions = explode(',', $this->getProperty('project.relatedExtensions'));
        foreach ($relatedExtensions as $relatedExtension) {
            $path = $this->getProperty('project.' . $relatedExtension . '.path');
            if (empty($path)) {
                $path = $basePath . '/extensions/' . $relatedExtension;
                if (is_dir($path)) {
                    $xpath     = sprintf("alledia/relatedExtensions/extension[text()='%s']", $relatedExtension);
                    $extension = $manifest->xpath($xpath);
                    $extension = reset($extension);

                    $type    = (string)$extension['type'];
                    $element = (string)$extension['element'];
                    if ($extension instanceof SimpleXMLElement && $type && $element) {
                        if ($manifestPath = $this->findManifestFile($type, $element, $path)) {
                            $this->updateManifest($manifestPath);
                        }
                    }
                }
            }
        }
    }
}

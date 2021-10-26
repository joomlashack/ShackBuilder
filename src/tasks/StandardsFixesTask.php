<?php
/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2020 Joomlashack.com. All rights reserved
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

class StandardsFixesTask extends Task
{
    use TraitShack;

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
        'creationDate' => null,
        'version'      => null,
        'variant'      => null
    ];

    /**
     * @inheritDoc
     */
    public function main()
    {
        $manifestPath = $this->getProperty('project.manifest');
        if (is_file($manifestPath)) {
            $this->updateManifest($manifestPath);

        } else {
            $this->throwError('Manifest not found: ' . $manifestPath);
        }

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
            $fixed    = str_replace('.', '-', $match[0]);
            $composer = str_replace($match[0], $fixed, $composer);
        }
        if (preg_match('#"license"\s*:\s*"(.*)"#', $composer, $match)) {
            $fixed    = str_replace($match[1], 'GPL-2.0-or-later', $match[0]);
            $composer = str_replace($match[0], $fixed, $composer);
        }
        if (preg_match('#"php"\s*:\s*"(.*)"#', $composer, $match)) {
            $fixed = str_replace($match[1], '>=7.2.5', $match[0]);
            $composer = str_replace($match[0], $fixed, $composer);
        }
        if (preg_match('#"target-platform"\s*:\s*"(.*)"#', $composer, $match)) {
            $fixed = str_replace($match[1], '.*', $match[0]);
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
     * @return void
     */
    protected function updateManifest($manifestPath = null)
    {
        $manifestPath = $manifestPath ?: $this->getProperty('project.manifest');
        if (is_file($manifestPath) == false) {
            $this->throwError('Manifest not found: ' . $manifestPath);
        }

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
                $divider = $match[1];
                if ($value === null) {
                    $value = $divider . trim($match[0]);

                } else {
                    $value = $divider . $value;
                }

                $manifestString = str_replace($match[0], '', $manifestString);

            } elseif ($value) {
                $value = $divider . $value;
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
}

<?php

/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2017-2026 Joomlashack.com. All rights reserved
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

/**
 * Task to update Wright version and other template details
 *
 * @package     Wright
 * @subpackage  Phing Tasks
 * @since       3.0
 */
class TemplateDetailsTask extends Task
{
    /**
     * The file to generate
     */
    private $todir = null;

    private $version = null;

    private $templateName = null;

    private $documentationLink = null;

    /**
     * Sets the destination file
     *
     * @param string $str Destination file
     *
     * @return  void
     */
    public function setTodir($str)
    {
        $this->todir = $str;
    }

    /**
     * Sets Wright version
     *
     * @param string $str Version
     *
     * @return  void
     */
    public function setVersion($str)
    {
        $this->version = $str;
    }

    /**
     * Sets the template name
     *
     * @param string $str Template Name
     *
     * @return  void
     */
    public function setTemplateName($str)
    {
        $this->templateName = $str;
    }

    /**
     * Sets the documentation link
     *
     * @param string $str Documentation link
     *
     * @return  void
     */
    public function setDocumentationLink($str)
    {
        $this->documentationLink = $str;
    }

    /**
     * The init method: Do init steps
     *
     * @return  void
     */
    public function init()
    {
        // Nothing to do here
    }

    /**
     * Main entry of this phing task
     *
     * @return  void
     */
    public function main()
    {
        if (file_exists($this->todir . '/wright/wright.php')) {
            $this->updateFile('/wright/wright.php', 'version', $this->version);
        }

        if (file_exists($this->todir . '/wrighttemplate.php')) {
            $this->updateFile('/wrighttemplate.php', 'templateName', $this->templateName);
            $this->updateFile('/wrighttemplate.php', 'documentationLink', $this->documentationLink);
        }
    }

    /**
     * Updates the given file
     *
     * @param string $fileName File to update
     * @param string $variable Variable to update
     * @param string $data     Data to set instead of the variable
     *
     * @return  void
     */
    protected function updateFile($fileName, $variable, $data)
    {
        $file = file_get_contents($this->todir . $fileName);
        $file = str_replace('{' . $variable . '}', $data, $file);
        file_put_contents($this->todir . $fileName, $file);
        $this->log('Updated ' . $variable . ' to ' . $data . ' in file ' . $fileName);
    }
}

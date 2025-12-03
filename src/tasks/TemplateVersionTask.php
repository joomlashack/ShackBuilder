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

class TemplateVersionTask extends Task
{

    /**
     * The file to generate
     */
    private $todir = null;

    private $template = null;

    private $version = null;

    /**
     * The setter for the attribute "template"
     */
    public function setTemplate($str)
    {
        $this->template = $str;
    }

    /**
     * The setter for the attribute "todir"
     */
    public function setTodir($str)
    {
        $this->todir = $str;
    }

    public function setVersion($str)
    {
        $this->version = $str;
    }

    /**
     * The init method: Do init steps.
     */
    public function init()
    {
        // nothing to do here
    }

    /**
     * The main entry point method.
     */
    public function main()
    {
        if (file_exists($this->todir . '/language/en-GB')) {
            $this->updateLanguageFile('en-GB/en-GB.tpl_' . $this->template . '.ini');
            $this->updateLanguageFile('en-GB/en-GB.tpl_' . $this->template . '.sys.ini');
        }

        if (file_exists($this->todir . '/language/es-ES')) {
            $this->updateLanguageFile('es-ES/es-ES.tpl_' . $this->template . '.ini');
            $this->updateLanguageFile('es-ES/es-ES.tpl_' . $this->template . '.sys.ini');
        }

        if (file_exists($this->todir . '/language/de-DE')) {
            $this->updateLanguageFile('de-DE/de-DE.tpl_' . $this->template . '.ini');
            $this->updateLanguageFile('de-DE/de-DE.tpl_' . $this->template . '.sys.ini');
        }

        if (file_exists($this->todir . '/wright/wright.php')) {
            $this->updateFile('/wright/wright.php');
        }
    }

    protected function updateLanguageFile($fileName)
    {
        $path = '/language/' . $fileName;

        $this->updateFile($path);
    }

    protected function updateFile($path)
    {
        $path = $this->todir . $path;

        if (!file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);
        $content = str_replace('{version}', $this->version, $content);

        file_put_contents($path, $content);

        $this->log("Updated version in file " . $path);
    }
}

<?php
/**
 * @package   AllediaBuilder
 * @contact   www.joomlashack.com, hello@joomlashack.com
 * @copyright 2017 Joomlashack.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once "phing/Task.php";

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
	
	protected function updateLanguageFile($fileName)
	{
		$path = '/language/' . $fileName;

		$this->updateFile($path);
	}

	protected function updateFile($path)
	{
		$path = $this->todir . $path;

		if (! file_exists($path)) {
			return ;
		}

		$content = file_get_contents($path);
		$content = str_replace('{version}', $this->version, $content);
	
		file_put_contents($path, $content);

		$this->log("Updated version in file " . $path);
	}

    /**
     * The main entry point method.
     */
    public function main()
    {
		if (file_exists($this->todir . '/language/en-GB'))
		{
			$this->updateLanguageFile('en-GB/en-GB.tpl_' . $this->template . '.ini');
			$this->updateLanguageFile('en-GB/en-GB.tpl_' . $this->template . '.sys.ini');
		}

		if (file_exists($this->todir . '/language/es-ES'))
		{
			$this->updateLanguageFile('es-ES/es-ES.tpl_' . $this->template . '.ini');
			$this->updateLanguageFile('es-ES/es-ES.tpl_' . $this->template . '.sys.ini');
		}

		if (file_exists($this->todir . '/language/de-DE'))
		{
			$this->updateLanguageFile('de-DE/de-DE.tpl_' . $this->template . '.ini');
			$this->updateLanguageFile('de-DE/de-DE.tpl_' . $this->template . '.sys.ini');
		}

		if (file_exists($this->todir . '/wright/wright.php'))
		{
			$this->updateFile('/wright/wright.php');
		}
    }
}
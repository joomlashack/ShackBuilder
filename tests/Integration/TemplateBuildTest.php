<?php

/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2026 Joomlashack.com. All rights reserved
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

namespace ShackBuilder\Tests\Integration;

use ShackBuilder\Tests\Support\PhingTestCase;
use ZipArchive;

/**
 * Templates take their own path through build-template/build-assets
 */
class TemplateBuildTest extends PhingTestCase
{
    public function testTemplateBuild()
    {
        $path = $this->createTemplate([
            'src/index.php'                         => '<?php // template',
            'src/language/en-GB/en-GB.tpl_foo.ini'  => 'TPL_FOO_VERSION="{version}"',
            'src/language/es-ES/es-ES.tpl_foo.ini'  => 'TPL_FOO_VERSION="{version}"',
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $package = $path . '/packages/tpl_foo_free_1.2.3.zip';
        $this->assertSame(
            [
                'index.php',
                'language/en-GB/en-GB.tpl_foo.ini',
                'language/es-ES/es-ES.tpl_foo.ini',
                'templateDetails.xml',
            ],
            $this->zipEntries($package)
        );
        $this->assertSame('TPL_FOO_VERSION="1.2.3"', $this->zipContent($package, 'language/en-GB/en-GB.tpl_foo.ini'));
        $this->assertSame('TPL_FOO_VERSION="1.2.3"', $this->zipContent($package, 'language/es-ES/es-ES.tpl_foo.ini'));

        // Language files in the source keep the placeholder
        $this->assertSame('TPL_FOO_VERSION="{version}"', $this->readFile($path . '/src/language/en-GB/en-GB.tpl_foo.ini'));

        // css and js folders are always created
        $zip = new ZipArchive();
        $zip->open($package);
        $this->assertNotFalse($zip->locateName('css/'));
        $this->assertNotFalse($zip->locateName('js/'));
        $zip->close();
    }

    public function testWrightTemplateBuild()
    {
        $path = $this->createTemplate([
            'src/language/en-GB/en-GB.tpl_foo.ini' => 'TPL_FOO_VERSION="{version}"',
            'readme.properties'                    => "readme.docs=https://www.joomlashack.com/docs/foo\n",

            'wright/src/templateDetails.xml'       => '<extension type="template"><version>3.0.0</version></extension>',
            'wright/src/wright/wright.php'         => '<?php $version = "{version}";',
            'wright/src/index.php'                 => '<?php // wright index',
            'wright/src/favicon.ico'               => 'icon',
            'wright/src/component.php'             => '<?php // component',
            'wright/src/wrighttemplate.php'        => '<?php $name = "{templateName}"; $docs = "{documentationLink}";',
            'wright/src/html/layout.php'           => '<?php',
            'wright/src/css/wright.css'            => 'body {}',
            'wright/src/js/wright.js'              => 'var w;',
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $package = $path . '/packages/tpl_foo_free_1.2.3.zip';
        $this->assertSame(
            [
                'component.php',
                'css/wright.css',
                'favicon.ico',
                'html/layout.php',
                'index.php',
                'js/wright.js',
                'language/en-GB/en-GB.tpl_foo.ini',
                'templateDetails.xml',
                'wright/wright.php',
                'wrighttemplate.php',
            ],
            $this->zipEntries($package)
        );
        $this->assertSame('<?php $version = "1.2.3_3.0.0";', $this->zipContent($package, 'wright/wright.php'));
        $this->assertSame(
            '<?php $name = "foo"; $docs = "https://www.joomlashack.com/docs/foo";',
            $this->zipContent($package, 'wrighttemplate.php')
        );
    }

    /**
     * @param array $files
     *
     * @return string
     */
    protected function createTemplate(array $files): string
    {
        return $this->createExtension('Foo', [
            'type'         => 'template',
            'element'      => 'tpl_foo',
            'title'        => 'foo',
            'manifestName' => 'templateDetails.xml',
            'files'        => $files,
        ]);
    }
}

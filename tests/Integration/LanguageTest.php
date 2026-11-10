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

/**
 * merge-languages and copy-language
 */
class LanguageTest extends PhingTestCase
{
    public function testMergeLanguagesForProPlugin()
    {
        $this->createExtension('Foo', [
            'files' => [
                'src/language/en-GB/en-GB.plg_system_foo.ini'     => "FREE=\"1\"\n",
                'src/language/en-GB/en-GB.plg_system_foo.sys.ini' => "FREE_SYS=\"1\"\n",
            ],
        ]);
        $pro = $this->createExtension('Foo-Pro', [
            'license'    => 'pro',
            'files'      => [
                'src/language/en-GB/en-GB.plg_system_foo.pro.ini'     => "PRO=\"1\"\n",
                'src/language/en-GB/en-GB.plg_system_foo.pro.sys.ini' => "PRO_SYS=\"1\"\n",
            ],
            'properties' => ['project.Foo.path' => $this->tmpPath . '/Foo'],
        ]);

        $result = $this->runPhing($pro, ['merge-languages']);

        $this->assertBuildSucceeded($result);
        $folder = $pro . '/packages/dev/language/en-GB';
        $this->assertSame(
            ['en-GB.plg_system_foo.ini', 'en-GB.plg_system_foo.sys.ini'],
            array_values(array_diff(scandir($folder), ['.', '..']))
        );
        $this->assertSame("FREE=\"1\"\nPRO=\"1\"\n", $this->readFile($folder . '/en-GB.plg_system_foo.ini'));
        $this->assertSame("FREE_SYS=\"1\"\nPRO_SYS=\"1\"\n", $this->readFile($folder . '/en-GB.plg_system_foo.sys.ini'));

        // Sources are untouched
        $this->assertFileExists($pro . '/src/language/en-GB/en-GB.plg_system_foo.pro.ini');
    }

    public function testMergeLanguagesForProComponent()
    {
        $this->createExtension('Foo', [
            'type'    => 'component',
            'element' => 'com_foo',
            'files'   => [
                'src/admin/language/en-GB/en-GB.com_foo.ini' => "ADMIN_FREE=\"1\"\n",
                'src/site/language/en-GB/en-GB.com_foo.ini'  => "SITE_FREE=\"1\"\n",
            ],
        ]);
        $pro = $this->createExtension('Foo-Pro', [
            'type'       => 'component',
            'element'    => 'com_foo',
            'license'    => 'pro',
            'files'      => [
                'src/admin/language/en-GB/en-GB.com_foo.pro.ini' => "ADMIN_PRO=\"1\"\n",
                'src/site/language/en-GB/en-GB.com_foo.pro.ini'  => "SITE_PRO=\"1\"\n",
            ],
            'properties' => ['project.Foo.path' => $this->tmpPath . '/Foo'],
        ]);

        $result = $this->runPhing($pro, ['merge-languages']);

        $this->assertBuildSucceeded($result);
        $this->assertSame(
            "ADMIN_FREE=\"1\"\nADMIN_PRO=\"1\"\n",
            $this->readFile($pro . '/packages/dev/admin/language/en-GB/en-GB.com_foo.ini')
        );
        $this->assertSame(
            "SITE_FREE=\"1\"\nSITE_PRO=\"1\"\n",
            $this->readFile($pro . '/packages/dev/site/language/en-GB/en-GB.com_foo.ini')
        );
    }

    public function testMergeLanguagesForFreeExtensionCopiesFiles()
    {
        $path = $this->createExtension('Foo', [
            'files' => ['src/language/en-GB/en-GB.plg_system_foo.ini' => "FREE=\"1\"\n"],
        ]);

        $result = $this->runPhing($path, ['merge-languages']);

        $this->assertBuildSucceeded($result);
        $this->assertSame(
            "FREE=\"1\"\n",
            $this->readFile($path . '/packages/dev/language/en-GB/en-GB.plg_system_foo.ini')
        );
    }

    public function testMergeLanguagesReplacesOldFiles()
    {
        $path = $this->createExtension('Foo', [
            'files' => [
                'src/language/en-GB/en-GB.plg_system_foo.ini'          => "FREE=\"1\"\n",
                'packages/dev/language/en-GB/en-GB.plg_system_old.ini' => 'OLD',
            ],
        ]);

        $result = $this->runPhing($path, ['merge-languages']);

        $this->assertBuildSucceeded($result);
        $this->assertFileDoesNotExist($path . '/packages/dev/language/en-GB/en-GB.plg_system_old.ini');
    }

    public function testCopyLanguage()
    {
        $path = $this->createExtension('Foo', [
            'files' => [
                'src/language/en-GB/en-GB.plg_system_foo.ini'     => 'FOO="Foo"',
                'src/language/en-GB/en-GB.plg_system_foo.sys.ini' => 'FOO_SYS="Foo"',
            ],
        ]);

        $result = $this->runPhing($path, ['copy-language'], "en-GB\nfr-FR\n");

        $this->assertBuildSucceeded($result);
        $target = $path . '/foo.language.fr-FR/language/fr-FR';
        $this->assertSame('FOO="Foo"', $this->readFile($target . '/fr-FR.plg_system_foo.ini'));
        $this->assertSame('FOO_SYS="Foo"', $this->readFile($target . '/fr-FR.plg_system_foo.sys.ini'));
    }

    public function testCopyLanguageDefaultsToEnglish()
    {
        $path = $this->createExtension('Foo', [
            'files' => ['src/language/en-GB/en-GB.plg_system_foo.ini' => 'FOO="Foo"'],
        ]);

        // Empty answer takes the en-GB default
        $result = $this->runPhing($path, ['copy-language'], "\nde-DE\n");

        $this->assertBuildSucceeded($result);
        $this->assertFileExists($path . '/foo.language.de-DE/language/de-DE/de-DE.plg_system_foo.ini');
    }
}

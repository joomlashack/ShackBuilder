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
 * Pro builds: free source + pro source + merged language files
 */
class ProBuildTest extends PhingTestCase
{
    public function testProPluginMergesFreeSource()
    {
        $this->createExtension('Foo', [
            'files' => [
                'src/foo.php'                                 => '<?php // free',
                'src/free-only.php'                           => '<?php // free only',
                'src/language/en-GB/en-GB.plg_system_foo.ini' => "FREE=\"Free\"\n",
            ],
        ]);
        $pro = $this->createExtension('Foo-Pro', [
            'license'    => 'pro',
            'version'    => '1.2.3',
            'files'      => [
                'src/foo.php'                                     => '<?php // pro',
                'src/pro-only.php'                                => '<?php // pro only',
                'src/language/en-GB/en-GB.plg_system_foo.pro.ini' => "PRO=\"Pro\"\n",
            ],
            'properties' => ['project.Foo.path' => $this->tmpPath . '/Foo'],
        ]);

        $result = $this->runPhing($pro, ['build']);

        $this->assertBuildSucceeded($result);
        $package = $pro . '/packages/plg_system_foo_pro_1.2.3.zip';
        $this->assertSame(
            [
                'foo.php',
                'foo.xml',
                'free-only.php',
                'language/en-GB/en-GB.plg_system_foo.ini',
                'pro-only.php',
            ],
            $this->zipEntries($package)
        );
        $this->assertSame('<?php // pro', $this->zipContent($package, 'foo.php'));
        $this->assertSame(
            "FREE=\"Free\"\nPRO=\"Pro\"\n",
            $this->zipContent($package, 'language/en-GB/en-GB.plg_system_foo.ini')
        );
    }

    public function testProManifestIsUsed()
    {
        $this->createExtension('Foo', [
            'version' => '1.0.0',
            'files'   => ['src/language/en-GB/en-GB.plg_system_foo.ini' => ''],
        ]);
        $pro = $this->createExtension('Foo-Pro', [
            'license'    => 'pro',
            'version'    => '2.0.0',
            'properties' => ['project.Foo.path' => $this->tmpPath . '/Foo'],
        ]);

        $result = $this->runPhing($pro, ['build']);

        $this->assertBuildSucceeded($result);
        $package = $pro . '/packages/plg_system_foo_pro_2.0.0.zip';
        $this->assertStringContainsString('<version>2.0.0</version>', $this->zipContent($package, 'foo.xml'));
    }

    public function testProComponentMergesAdminAndSiteLanguages()
    {
        $this->createExtension('Foo', [
            'type'    => 'component',
            'element' => 'com_foo',
            'files'   => [
                'src/admin/language/en-GB/en-GB.com_foo.ini'     => "ADMIN_FREE=\"1\"\n",
                'src/admin/language/en-GB/en-GB.com_foo.sys.ini' => "SYS_FREE=\"1\"\n",
                'src/site/language/en-GB/en-GB.com_foo.ini'      => "SITE_FREE=\"1\"\n",
            ],
        ]);
        $pro = $this->createExtension('Foo-Pro', [
            'type'       => 'component',
            'element'    => 'com_foo',
            'license'    => 'pro',
            'files'      => [
                'src/admin/language/en-GB/en-GB.com_foo.pro.ini'     => "ADMIN_PRO=\"1\"\n",
                'src/admin/language/en-GB/en-GB.com_foo.pro.sys.ini' => "SYS_PRO=\"1\"\n",
                'src/site/language/en-GB/en-GB.com_foo.pro.ini'      => "SITE_PRO=\"1\"\n",
            ],
            'properties' => ['project.Foo.path' => $this->tmpPath . '/Foo'],
        ]);

        $result = $this->runPhing($pro, ['build']);

        $this->assertBuildSucceeded($result);
        $package = $pro . '/packages/com_foo_pro_1.2.3.zip';
        $this->assertSame(
            [
                'admin/language/en-GB/en-GB.com_foo.ini',
                'admin/language/en-GB/en-GB.com_foo.sys.ini',
                'foo.xml',
                'site/language/en-GB/en-GB.com_foo.ini',
            ],
            $this->zipEntries($package)
        );
        $this->assertSame(
            "ADMIN_FREE=\"1\"\nADMIN_PRO=\"1\"\n",
            $this->zipContent($package, 'admin/language/en-GB/en-GB.com_foo.ini')
        );
        $this->assertSame(
            "SYS_FREE=\"1\"\nSYS_PRO=\"1\"\n",
            $this->zipContent($package, 'admin/language/en-GB/en-GB.com_foo.sys.ini')
        );
        $this->assertSame(
            "SITE_FREE=\"1\"\nSITE_PRO=\"1\"\n",
            $this->zipContent($package, 'site/language/en-GB/en-GB.com_foo.ini')
        );
    }

    public function testProWithoutFreeVersion()
    {
        $pro = $this->createExtension('Foo-Pro', [
            'license'    => 'pro',
            'files'      => [
                'src/foo.php'                                 => '<?php // pro',
                'src/language/en-GB/en-GB.plg_system_foo.ini' => "PRO=\"Pro\"\n",
            ],
            'properties' => ['project.hasFreeVersion' => '0'],
        ]);

        $result = $this->runPhing($pro, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertSame(
            ['foo.php', 'foo.xml', 'language/en-GB/en-GB.plg_system_foo.ini'],
            $this->zipEntries($pro . '/packages/plg_system_foo_pro_1.2.3.zip')
        );
    }

    public function testProPluginWithoutLanguageFolder()
    {
        $this->createExtension('Foo', ['files' => ['src/foo.php' => '<?php // free']]);
        $pro = $this->createExtension('Foo-Pro', [
            'license'    => 'pro',
            'files'      => ['src/pro.php' => '<?php // pro'],
            'properties' => ['project.Foo.path' => $this->tmpPath . '/Foo'],
        ]);

        $result = $this->runPhing($pro, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertSame(
            ['foo.php', 'foo.xml', 'pro.php'],
            $this->zipEntries($pro . '/packages/plg_system_foo_pro_1.2.3.zip')
        );
    }

    public function testProBuildNeedsFreePath()
    {
        $pro = $this->createExtension('Foo-Pro', ['license' => 'pro']);

        $result = $this->runPhing($pro, ['build']);

        $this->assertBuildFailed($result, 'Missing path to Free version: project.Foo.path property');
    }
}

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
 * Minifying and SCSS compiling, run by pre-build and by every build
 */
class PreBuildTest extends PhingTestCase
{
    protected const SCRIPT = <<<JS
// A comment
function foo(value) {
    return value + 1;
}

JS;

    protected const SCSS = <<<SCSS
\$color: #336699;
.foo {
    .bar { color: \$color; }
}

SCSS;

    public function testPreBuildMinifiesWithoutPackaging()
    {
        $path = $this->createExtension('Foo', [
            'alledia' => '<minify><script>media/js/foo.js</script></minify>',
            'files'   => ['src/media/js/foo.js' => static::SCRIPT],
        ]);

        $result = $this->runPhing($path, ['pre-build']);

        $this->assertBuildSucceeded($result);
        $this->assertStringContainsString(
            'function foo(value){return value+1;}',
            $this->readFile($path . '/src/media/js/foo.min.js')
        );
        $this->assertDirectoryDoesNotExist($path . '/packages');
    }

    public function testBuildIncludesMinifiedFiles()
    {
        $path = $this->createExtension('Foo', [
            'alledia' => <<<XML
        <minify>
            <script>media/js/foo.js</script>
            <scripts destination="media/js/bundle.js">
                <script>media/js/foo.js</script>
                <script>media/js/bar.js</script>
            </scripts>
        </minify>
XML,
            'files'   => [
                'src/media/js/foo.js' => static::SCRIPT,
                'src/media/js/bar.js' => "var bar = 1;\n",
            ],
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildSucceeded($result);
        $this->assertSame(
            [
                'foo.xml',
                'media/js/bar.js',
                'media/js/bundle.js',
                'media/js/bundle.min.js',
                'media/js/foo.js',
                'media/js/foo.min.js',
            ],
            $this->zipEntries($path . '/packages/plg_system_foo_free_1.2.3.zip')
        );
    }

    public function testMinifySuffixFromManifest()
    {
        $path = $this->createExtension('Foo', [
            'alledia' => '<minify suffix="-min"><script>media/js/foo.js</script></minify>',
            'files'   => ['src/media/js/foo.js' => static::SCRIPT],
        ]);

        $result = $this->runPhing($path, ['pre-build']);

        $this->assertBuildSucceeded($result);
        $this->assertFileExists($path . '/src/media/js/foo-min.js');
    }

    public function testInvalidScriptFailsBuild()
    {
        $path = $this->createExtension('Foo', [
            'alledia' => '<minify><script>media/js/foo.js</script></minify>',
            'files'   => ['src/media/js/foo.js' => "var foo = 'unterminated;\n"],
        ]);

        $result = $this->runPhing($path, ['build']);

        $this->assertBuildFailed($result, 'Unable to minify media/js/foo.js: Unclosed string');
        $this->assertFileDoesNotExist($path . '/packages/plg_system_foo_free_1.2.3.zip');
    }

    public function testScssNeedsSass()
    {
        $path = $this->createExtension('Foo', [
            'alledia' => '<scss><file>media/css/style.scss</file></scss>',
            'files'   => ['src/media/css/style.scss' => static::SCSS],
        ]);

        // The default test PATH leaves out node_modules and wherever else sass is installed
        $result = $this->runPhing($path, ['pre-build']);

        $this->assertBuildFailed($result, 'sass command is not available');
    }

    public function testScssIsCompiled()
    {
        $this->requireSass();

        $path = $this->createExtension('Foo', [
            'alledia' => '<scss><file>media/css/style.scss</file></scss>',
            'files'   => ['src/media/css/style.scss' => static::SCSS],
        ]);

        $result = $this->runPhing($path, ['pre-build'], '', true);

        $this->assertBuildSucceeded($result);
        $this->assertSame('.foo .bar{color:#369}', trim($this->readFile($path . '/src/media/css/style.css')));
    }

    public function testScssStyleAndDestination()
    {
        $this->requireSass();

        $path = $this->createExtension('Foo', [
            'alledia' => <<<XML
        <scss>
            <file>scss/style.scss</file>
            <destination>media/css</destination>
            <style>expanded</style>
        </scss>
XML,
            'files'   => ['src/scss/style.scss' => static::SCSS],
        ]);

        $result = $this->runPhing($path, ['pre-build'], '', true);

        $this->assertBuildSucceeded($result);
        $this->assertSame(
            ".foo .bar {\n  color: #336699;\n}",
            trim($this->readFile($path . '/src/media/css/style.css'))
        );
    }

    public function testSeveralScssFiles()
    {
        $this->requireSass();

        $path = $this->createExtension('Foo', [
            'alledia' => <<<XML
        <scss>
            <file>media/css/one.scss</file>
            <file>media/css/two.scss</file>
        </scss>
XML,
            'files'   => [
                'src/media/css/one.scss' => '.one { color: red; }',
                'src/media/css/two.scss' => '.two { color: blue; }',
            ],
        ]);

        $result = $this->runPhing($path, ['build'], '', true);

        $this->assertBuildSucceeded($result);
        $package = $path . '/packages/plg_system_foo_free_1.2.3.zip';
        $this->assertSame('.one{color:red}', trim($this->zipContent($package, 'media/css/one.css')));
        $this->assertSame('.two{color:blue}', trim($this->zipContent($package, 'media/css/two.css')));
    }

    public function testMissingScssFileFails()
    {
        $this->requireSass();

        $path = $this->createExtension('Foo', [
            'alledia' => '<scss><file>media/css/missing.scss</file></scss>',
        ]);

        $result = $this->runPhing($path, ['pre-build'], '', true);

        $this->assertBuildFailed($result, 'missing.scss: Does not exist');
    }

    public function testInvalidScssFails()
    {
        $this->requireSass();

        $path = $this->createExtension('Foo', [
            'alledia' => '<scss><file>media/css/style.scss</file></scss>',
            'files'   => ['src/media/css/style.scss' => ".foo { color: \n"],
        ]);

        $result = $this->runPhing($path, ['pre-build'], '', true);

        $this->assertBuildFailed($result, 'SCSS Compile Failed');
    }

    /**
     * @return void
     */
    protected function requireSass()
    {
        $localSass = static::BUILDER_ROOT . '/node_modules/.bin/sass';

        exec('command -v ' . (is_file($localSass) ? 'node' : 'sass'), $output, $exitCode);
        if ($exitCode !== 0) {
            $this->markTestSkipped('sass is not installed. Run npm install in ShackBuilder');
        }
    }
}

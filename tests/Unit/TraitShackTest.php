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

namespace ShackBuilder\Tests\Unit;

use Phing\Task;
use Phing\Task\System\EchoTask;
use PHPUnit\Framework\Attributes\DataProvider;
use ShackBuilder\Tasks\TraitShack;
use ShackBuilder\Tests\Support\TaskTestCase;

class TraitShackTest extends TaskTestCase
{
    /**
     * @var Task
     */
    protected $task = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Expose the trait's protected methods
        $this->task = new class extends Task {
            use TraitShack {
                getProperty as public;
                setProperty as public;
                setProperties as public;
                tryXmlFunctions as public;
                findManifestFile as public;
                findRegexStrings as public;
                initProjectTask as public;
            }

            public function main()
            {
            }
        };
        $this->task->setProject($this->project);
        $this->task->setOwningTarget($this->target);
    }

    public function testGetProperty()
    {
        $this->project->setProperty('foo', 'bar');

        $this->assertSame('bar', $this->task->getProperty('foo'));
        $this->assertNull($this->task->getProperty('missing'));
    }

    public function testGetPropertyDefaultCanBeAnotherProperty()
    {
        $this->project->setProperty('fallback', 'from fallback');

        $this->assertSame('from fallback', $this->task->getProperty('missing', 'fallback'));
        $this->assertSame('literal', $this->task->getProperty('missing', 'literal'));
    }

    public function testSetPropertyReturnsPreviousValue()
    {
        $this->assertNull($this->task->setProperty('foo', 'one'));
        $this->assertSame('one', $this->task->setProperty('foo', 'two'));
        $this->assertSame('two', $this->project->getProperty('foo'));
    }

    public function testSetPropertiesFlattensArrays()
    {
        $this->task->setProperties('root', [
            'name'  => 'foo',
            'extra' => [
                'element' => 'plg_system_foo',
                'deep'    => ['value' => 'x'],
            ],
        ]);

        $this->assertSame('foo', $this->project->getProperty('root.name'));
        $this->assertSame('plg_system_foo', $this->project->getProperty('root.extra.element'));
        $this->assertSame('x', $this->project->getProperty('root.extra.deep.value'));
    }

    public function testSetPropertiesIgnoresListedNames()
    {
        $this->task->setProperties(
            'root',
            [
                'name'  => 'foo',
                'extra' => ['element' => 'plg_system_foo', 'skip' => 'me'],
                'other' => ['value' => 'x'],
            ],
            ['root.extra.skip', 'root.other']
        );

        $this->assertSame('foo', $this->project->getProperty('root.name'));
        $this->assertSame('plg_system_foo', $this->project->getProperty('root.extra.element'));
        $this->assertNull($this->project->getProperty('root.extra.skip'));
        $this->assertNull($this->project->getProperty('root.other.value'));
    }

    public function testSetPropertiesIgnoreAcceptsString()
    {
        $this->task->setProperties('root', ['a' => '1', 'b' => '2'], 'root.b');

        $this->assertSame('1', $this->project->getProperty('root.a'));
        $this->assertNull($this->project->getProperty('root.b'));
    }

    public function testTryXmlFunctionsReturnsResult()
    {
        $xml = $this->task->tryXmlFunctions(fn() => simplexml_load_string('<root><a>1</a></root>'));

        $this->assertSame('1', (string)$xml->a);
    }

    public function testTryXmlFunctionsReturnsNullForEmptyResult()
    {
        $this->assertNull($this->task->tryXmlFunctions(fn() => ''));
    }

    public static function manifestProvider(): array
    {
        return [
            'component'             => ['component', 'foo', 'foo.xml'],
            'component with prefix' => ['component', 'com_foo', 'foo.xml'],
            'plugin'                => ['plugin', 'foo', 'foo.xml'],
            'library'               => ['library', 'foo', 'foo.xml'],
            'module'                => ['module', 'foo', 'mod_foo.xml'],
            'module with prefix'    => ['module', 'mod_foo', 'mod_foo.xml'],
            'template'              => ['template', 'foo', 'templateDetails.xml'],
        ];
    }

    #[DataProvider('manifestProvider')]
    public function testFindManifestFile(string $type, string $element, string $fileName)
    {
        $expected = $this->writeFile('extension/' . $fileName, '<extension/>');

        $this->assertSame($expected, $this->task->findManifestFile($type, $element, $this->tmpPath . '/extension'));
    }

    public function testFindRegexStrings()
    {
        $subject = "<a>\n    <packager>x</packager>\n    <b>y</b>\n    <packagerurl>z</packagerurl>\n</a>";

        $this->assertSame(
            ["\n    <packager>x</packager>", "\n    <packagerurl>z</packagerurl>"],
            $this->task->findRegexStrings(['<packager>.*</packager>', '<packagerurl>.*</packagerurl>'], $subject)
        );
        $this->assertSame([], $this->task->findRegexStrings(['<missing>.*</missing>'], $subject));
    }

    public function testFindRegexStringsEscapesDelimiter()
    {
        $this->assertSame([' a#b'], $this->task->findRegexStrings(['a#b'], 'x a#b y'));
    }

    public function testInitProjectTask()
    {
        $task = $this->task->initProjectTask('echo');

        $this->assertInstanceOf(EchoTask::class, $task);
        $this->assertSame($this->project, $task->getProject());
    }
}

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

namespace ShackBuilder\Tests\Support;

use Phing\Project;
use Phing\Target;
use Phing\Task;

/**
 * Runs custom tasks in-process against a bare Phing project.
 *
 * Tasks call die() on errors (TraitShack::throwError), so error paths
 * can't be tested here. See the Integration\TaskErrorsTest instead.
 */
abstract class TaskTestCase extends TestCase
{
    /**
     * @var Project
     */
    protected $project = null;

    /**
     * @var LogCollector
     */
    protected $logs = null;

    /**
     * Tasks always run inside a target in a real build
     *
     * @var Target
     */
    protected $target = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logs    = new LogCollector();
        $this->project = new Project();
        $this->project->init();
        $this->project->setBasedir($this->tmpPath);
        $this->project->addBuildListener($this->logs);

        $this->target = new Target();
        $this->target->setName('test');
        $this->target->setProject($this->project);
    }

    /**
     * @param array $properties
     *
     * @return void
     */
    protected function setProperties(array $properties)
    {
        foreach ($properties as $name => $value) {
            $this->project->setProperty($name, $value);
        }
    }

    /**
     * @template T of Task
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    protected function createTask(string $className): Task
    {
        /** @var Task $task */
        $task = new $className();
        $task->setProject($this->project);
        $task->setOwningTarget($this->target);
        $task->setTaskName(lcfirst(substr(strrchr($className, '\\'), 1)));
        $task->init();

        return $task;
    }
}

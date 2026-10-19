<?php

/**
 * @package   ShackBuilder
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2016-2026 Joomlashack.com. All rights reserved
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
 *
 */

/*
 * This task takes a list with delimited values, and executes a target for each value
 * with set param in parallel.
 *
 * ATTENTION: if the order of the individual tasks is important, do not use this!!!
 *
 * This is inspired by ParallelTask
 *
 * NOTE: Unfortunately the core-ForeachTask is not very open for extension (all members are private),
 *       So I copied the class and modified it, instead of inherited it.
 *
 * Usage:
 * <foreachParallel list="values" target="targ" param="name" delimiter="|" threadCount="4" />
 *
 * Attributes:
 * list        --> The list of values to process, with the delimiter character,
 *                 indicated by the "delimiter" attribute, separating each value.
 * target      --> The target to call for each token, passing the token as the
 *                 parameter with the name indicated by the "param" attribute.
 * param       --> The name of the parameter to pass the tokens in as to the
 *                 target.
 * delimiter   --> The delimiter string that separates the values in the "list"
 *                 parameter.  The default is ",".
 * threadCount --> Maximum number of threads / processes to use.
 *
 * @author    Matthias Krauser <matthias@krauser.eu>
 * @package   phing.tasks.ext
 */

namespace ShackBuilder\Tasks;

use Phing\Io\File;
use Phing\Io\FileSystem;
use Phing\Project;
use Phing\Task;
use Phing\Task\System\PhingCallTask;
use Phing\Type\FileList;
use Phing\Type\FileSet;
use Phing\Type\Mapper;
use Throwable;

class ForeachParallelTask extends Task
{
    use TraitShack;

    /**
     * @var string
     */
    protected $list = null;

    /** Name of parameter to pass to callee */
    protected $param = null;

    /** Name of absolute path parameter to pass to callee */
    protected $absolutePath = null;

    /** Delimiter that separates items in $list */
    protected $delimiter = ',';

    /** Maximum number of threads / processes */
    protected $threadCount = 2;

    /** Array of filesets */
    protected $filesets = [];

    /** Instance of mapper **/
    protected $mapperElement = null;

    /**
     * Array of filelists
     *
     * @var array
     */
    protected $filelists = [];

    /**
     * Target to execute.
     *
     * @var string
     */
    protected $calleeTarget;

    /**
     * Total number of files processed
     *
     * @var integer
     */
    protected $totalFiles = 0;

    /**
     * Total number of directories processed
     *
     * @var integer
     */
    protected $total_dirs = 0;

    /**
     * @return PhingCallTask
     */
    protected function getCallee(): PhingCallTask
    {
        /** @var PhingCallTask $callee */
        $callee = $this->project->createTask('phingcall');
        $callee->setOwningTarget($this->getOwningTarget());
        $callee->setTaskName($this->getTaskName());
        $callee->setLocation($this->getLocation());
        $callee->init();

        return $callee;
    }

    /**
     * This method does the work.
     *
     * @return void
     */
    public function main()
    {
        if ($this->list === null && count($this->filesets) == 0 && count($this->filelists) == 0) {
            $this->throwError('Need either list, nested fileset or nested filelist to iterate through');
        }
        if ($this->param === null) {
            $this->throwError('You must supply a property name to set on each iteration in param');
        }
        if ($this->calleeTarget === null) {
            $this->throwError('You must supply a target to perform');
        }

        if (!function_exists('pcntl_fork')) {
            $this->throwError(
                'ForeachParallelTask requires the pcntl extension to be loaded.',
                $this->getLocation()
            );
        }

        $callees = [];

        $mapper = null;

        if ($this->mapperElement !== null) {
            $mapper = $this->mapperElement->getImplementation();
        }

        $totalEntries = 0;

        if (trim($this->list)) {
            $values = explode($this->delimiter, $this->list);

            foreach ($values as $value) {
                $value     = trim($value);
                $preMapped = '';
                if ($mapper !== null) {
                    $preMapped = $value;
                    $value     = $mapper->main($value);
                    if ($value === null) {
                        continue;
                    }
                    $value = array_shift($value);
                }
                $this->log(
                    sprintf(
                        "Setting param '%s' to value '%s'%s",
                        $this->param,
                        $value,
                        ($preMapped ? " (mapped from '$preMapped')" : '')
                    ),
                    Project::MSG_VERBOSE
                );
                $callee = $this->getCallee();
                $callee->setTarget($this->calleeTarget);
                $callee->setInheritAll(true);
                $callee->setInheritRefs(true);

                $prop = $callee->createProperty();
                $prop->setOverride(true);
                $prop->setName($this->param);
                $prop->setValue($value);

                $callees[] = $callee;
                $totalEntries++;
            }
        }

        // fileLists
        foreach ($this->filelists as $fileList) {
            $srcFiles = $fileList->getFiles($this->project);

            $this->collect($callees, $fileList->getDir($this->project), $srcFiles, []);
        }

        // filesets
        foreach ($this->filesets as $fileset) {
            $ds       = $fileset->getDirectoryScanner($this->project);
            $srcFiles = $ds->getIncludedFiles();
            $srcDirs  = $ds->getIncludedDirectories();

            $this->collect($callees, $fileset->getDir($this->project), $srcFiles, $srcDirs);
        }

        $this->runParallel($callees);

        if ($this->list === null) {
            $this->log(
                sprintf(
                    'Processed %s directories and %s files',
                    $this->total_dirs,
                    $this->totalFiles
                ),
                Project::MSG_VERBOSE
            );

        } else {
            $this->log(
                sprintf(
                    'Processed %s %s in list',
                    $totalEntries,
                    $totalEntries > 1 ? 'entries' : 'entry'
                ),
                Project::MSG_VERBOSE
            );
        }
    }

    /**
     * Builds one callee per file/directory, each with its own independent
     * PhingCallTask instance (safe to fork, since nothing is shared/mutated
     * after being added to $callees).
     *
     * @param PhingCallTask[] $callees
     * @param File            $fromDir
     * @param array           $srcFiles
     * @param array           $srcDirs
     *
     * @return void
     */
    protected function collect(array &$callees, File $fromDir, array $srcFiles, array $srcDirs)
    {
        $mapper = null;

        if ($this->mapperElement !== null) {
            $mapper = $this->mapperElement->getImplementation();
        }

        $filecount        = count($srcFiles);
        $this->totalFiles += $filecount;

        for ($j = 0; $j < $filecount; $j++) {
            $callee = $this->buildCallee($mapper, $fromDir, $srcFiles[$j]);
            if ($callee !== null) {
                $callees[] = $callee;
            }
        }

        $dircount         = count($srcDirs);
        $this->total_dirs += $dircount;

        for ($j = 0; $j < $dircount; $j++) {
            $callee = $this->buildCallee($mapper, $fromDir, $srcDirs[$j]);
            if ($callee !== null) {
                $callees[] = $callee;
            }
        }
    }

    /**
     * @param ?object $mapper
     * @param File    $fromDir
     * @param string  $value
     *
     * @return ?PhingCallTask
     */
    protected function buildCallee($mapper, File $fromDir, string $value): ?PhingCallTask
    {
        $preMapped = '';

        $callee = $this->getCallee();
        $callee->setTarget($this->calleeTarget);
        $callee->setInheritAll(true);
        $callee->setInheritRefs(true);

        if ($this->absolutePath) {
            $prop = $callee->createProperty();
            $prop->setOverride(true);
            $prop->setName($this->absolutePath);
            $prop->setValue($fromDir . FileSystem::getFileSystem()->getSeparator() . $value);
        }

        if ($mapper !== null) {
            $preMapped = $value;
            $value     = $mapper->main($value);
            if ($value === null) {
                return null;
            }
            $value = array_shift($value);
        }

        if ($this->param) {
            $this->log(
                "Setting param '$this->param' to value '$value'" . ($preMapped ? " (mapped from '$preMapped')" : ''),
                Project::MSG_VERBOSE
            );
            $prop = $callee->createProperty();
            $prop->setOverride(true);
            $prop->setName($this->param);
            $prop->setValue($value);
        }

        return $callee;
    }

    /**
     * Runs each callee's target in its own forked child process, bounded by
     * threadCount concurrent children. Replaces the DocBlox parallel worker
     * library bundled with Phing 2.x, which has no equivalent in Phing 3.
     *
     * @param PhingCallTask[] $callees
     *
     * @return void
     */
    protected function runParallel(array $callees)
    {
        $active = [];

        foreach ($callees as $callee) {
            while (count($active) >= max(1, $this->threadCount)) {
                $this->reapOne($active);
            }

            $pid = pcntl_fork();
            if ($pid === -1) {
                $this->throwError('Unable to fork a process for parallel execution', $this->getLocation());

            } elseif ($pid === 0) {
                $exitCode = 0;
                try {
                    $callee->main();
                } catch (Throwable $error) {
                    $this->log($error->getMessage(), Project::MSG_ERR);
                    $exitCode = 1;
                }
                exit($exitCode);

            } else {
                $active[$pid] = true;
            }
        }

        while (!empty($active)) {
            $this->reapOne($active);
        }
    }

    /**
     * @param array $active Map of pid => true for still-running children
     *
     * @return void
     */
    protected function reapOne(array &$active)
    {
        $pid = pcntl_wait($status);
        if ($pid > 0 && isset($active[$pid])) {
            unset($active[$pid]);

            if (pcntl_wexitstatus($status) !== 0) {
                $this->throwError('A parallel task failed, see log above for details', $this->getLocation());
            }
        }
    }

    public function setList($list)
    {
        $this->list = (string)$list;
    }

    public function setTarget($target)
    {
        $this->calleeTarget = (string)$target;
    }

    public function setParam($param)
    {
        $this->param = (string)$param;
    }

    public function setAbsparam($absolutePath)
    {
        $this->absolutePath = (string)$absolutePath;
    }

    public function setDelimiter($delimiter)
    {
        $this->delimiter = (string)$delimiter;
    }

    /**
     * Sets the maximum number of threads / processes to use
     *
     * @param int $threadCount
     */
    public function setThreadCount($threadCount)
    {
        $this->threadCount = $threadCount;
    }

    /**
     * Nested adder, adds a set of files (nested fileset attribute).
     *
     * @return void
     */
    public function addFileSet(FileSet $fileset)
    {
        $this->filesets[] = $fileset;
    }

    /**
     * Nested creator, creates one Mapper for this task
     *
     * @access  public
     * @return object         The created Mapper type object
     */
    public function createMapper()
    {
        if ($this->mapperElement !== null) {
            $this->throwError('Cannot define more than one mapper', $this->getLocation());
        }
        $this->mapperElement = new Mapper($this->project);

        return $this->mapperElement;
    }

    /**
     * Supports embedded <filelist> element.
     *
     * @return FileList
     */
    public function createFileList()
    {
        $num = array_push($this->filelists, new FileList());

        return $this->filelists[$num - 1];
    }
}

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

use Phing\Listener\BuildEvent;
use Phing\Listener\BuildListener;

/**
 * Collects every message a Phing project logs, so tests can check them
 */
class LogCollector implements BuildListener
{
    /**
     * @var array[] [priority, message]
     */
    public $messages = [];

    /**
     * @param ?int $priority Only return messages at this level
     *
     * @return string[]
     */
    public function getMessages(?int $priority = null): array
    {
        $messages = [];
        foreach ($this->messages as [$level, $message]) {
            if ($priority === null || $priority == $level) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    /**
     * @param ?int $priority
     *
     * @return string
     */
    public function getText(?int $priority = null): string
    {
        return join("\n", $this->getMessages($priority));
    }

    public function messageLogged(BuildEvent $event)
    {
        $this->messages[] = [$event->getPriority(), $event->getMessage()];
    }

    public function buildStarted(BuildEvent $event)
    {
    }

    public function buildFinished(BuildEvent $event)
    {
    }

    public function targetStarted(BuildEvent $event)
    {
    }

    public function targetFinished(BuildEvent $event)
    {
    }

    public function taskStarted(BuildEvent $event)
    {
    }

    public function taskFinished(BuildEvent $event)
    {
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\EventHandler;

use DigipolisGent\Robo\Helpers\EventHandler\EventHandlerWithPriority;
use DigipolisGent\Robo\Helpers\Util\AddToContainerInterface;
use Robo\Tasks;

abstract class AbstractTaskEventHandler
    extends Tasks
    implements EventHandlerWithPriority, AddToContainerInterface
{

    /**
     * {@inheritDoc}
     */
    public function getPriority(): int
    {
        return static::DEFAULT_PRIORITY;
    }
}

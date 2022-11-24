<?php

namespace DigipolisGent\Robo\Helpers\EventHandler;

use Symfony\Component\EventDispatcher\GenericEvent;

interface EventHandlerWithPriority
{

    const DEFAULT_PRIORITY = 999;

    /**
     * Get the priority for this handler. Higher number means lower priority.
     *
     * @return int
     *   The priority.
     */
    public function getPriority(): int;

    /**
     * Handle the event, optionally stop propagation.
     *
     * @param GenericEvent $event
     *   The event object, contains relevant arguments.
     */
    public function handle(GenericEvent $event);
}

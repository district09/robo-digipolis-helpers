<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use Symfony\Component\EventDispatcher\GenericEvent;

class InstallHandler extends AbstractTaskEventHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        // Empty install handler implementation.
        return $this->collectionBuilder();
    }
}

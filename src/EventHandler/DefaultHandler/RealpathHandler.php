<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\Path;
use Symfony\Component\EventDispatcher\GenericEvent;

class RealpathHandler extends AbstractTaskEventHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event) {
        return Path::realpath($event->getArgument('path'));
    }
}

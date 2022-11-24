<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Traits\SwitchPreviousTrait;
use Symfony\Component\EventDispatcher\GenericEvent;

class SwitchPreviousHandler extends AbstractTaskEventHandler
{
    use SwitchPreviousTrait;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $releasesDir = $event->getArgument('releasesDir');
        $currentSymlink = $event->getArgument('currentSymlink');

        return $this->taskSwitchPrevious($releasesDir, $currentSymlink);
    }
}

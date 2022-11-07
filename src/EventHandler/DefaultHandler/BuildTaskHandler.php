<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\TimeHelper;
use Symfony\Component\EventDispatcher\GenericEvent;

class BuildTaskHandler extends AbstractTaskEventHandler
{

    use \DigipolisGent\Robo\Task\Package\Tasks;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $archiveName = $event->hasArgument('archiveName') ? $event->getArgument('archiveName') : null;
        $archive = is_null($archiveName) ? TimeHelper::getInstance()->getTime() . '.tar.gz' : $archiveName;

        return $this->taskPackageProject($archive);
    }
}

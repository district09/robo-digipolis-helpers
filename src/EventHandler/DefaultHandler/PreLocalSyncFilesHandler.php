<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use Symfony\Component\EventDispatcher\GenericEvent;

class PreLocalSyncFilesHandler extends AbstractTaskEventHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $localSettings = $event->getArgument('localSettings');

        return $this
            ->taskExecStack()
                ->exec(
                    (string) CommandBuilder::create('chown')
                        ->addFlag('R')
                        ->addRawArgument('$USER')
                        ->addArgument(dirname($localSettings['filesdir']))
                )
                ->exec(
                    (string) CommandBuilder::create('chmod')
                        ->addFlag('R')
                        ->addArgument('u+w')
                        ->addArgument(dirname($localSettings['filesdir']))
                );
    }
}

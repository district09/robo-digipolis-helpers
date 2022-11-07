<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractBackupHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use Symfony\Component\EventDispatcher\GenericEvent;

class RestoreBackupFilesLocalHandler extends AbstractBackupHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $localSettings = $event->getArgument('localSettings');
        $filesBackupFile =  $this->backupFileName('.tar.gz', $remoteSettings['time']);

        return $this->taskExecStack()
            ->exec(
                (string) CommandBuilder::create('rm')
                    ->addFlag('rf')
                    ->addArgument($localSettings['filesdir'] . '/*')
                    ->addArgument($localSettings['filesdir'] . '/.??*')
            )
            ->exec(
                (string) CommandBuilder::create('tar')
                    ->addFlag('xkz')
                    ->addFlag('f', $filesBackupFile)
                    ->addFlag('C', $localSettings['filesdir'])
            )
            ->exec(
                (string) CommandBuilder::create('rm')
                    ->addFlag('f')
                    ->addArgument($filesBackupFile)
            );
    }
}

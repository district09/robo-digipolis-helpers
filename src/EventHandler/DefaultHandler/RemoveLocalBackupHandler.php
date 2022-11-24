<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractBackupHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use Symfony\Component\EventDispatcher\GenericEvent;

class RemoveLocalBackupHandler extends AbstractBackupHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $options = $event->getArgument('options');
        $dbBackupFile = $this->backupFileName('.sql.gz', $remoteSettings['time']);
        $removeLocalBackup = CommandBuilder::create('rm')
            ->addFlag('f')
            ->addArgument($dbBackupFile);
        if ($options['files']) {
            $removeLocalBackup->addArgument($this->backupFileName('.tar.gz', $remoteSettings['time']));
        }

        return $this->taskExecStack()->exec((string) $removeLocalBackup);
    }
}

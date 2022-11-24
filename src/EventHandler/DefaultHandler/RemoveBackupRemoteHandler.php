<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractBackupHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class RemoveBackupRemoteHandler extends AbstractBackupHandler
{

    use \DigipolisGent\Robo\Helpers\Traits\RemoteDatabaseBackupTrait;
    use \DigipolisGent\Robo\Helpers\Traits\RemoteFilesBackupTrait;
    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $timeouts = $event->getArgument('timeouts');
        $backupDir = $remoteSettings['backupsdir'] . '/' . $remoteSettings['time'];

        $collection = $this->collectionBuilder();
        $collection->taskSsh($remoteConfig->getHost(), new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile()))
            ->timeout($timeouts['remove_backup'])
            ->exec(
                (string) CommandBuilder::create('rm')
                    ->addFlag('rf')
                    ->addArgument($backupDir)
            );

        return $collection;
    }
}

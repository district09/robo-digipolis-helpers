<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractBackupHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class UploadBackupHandler extends AbstractBackupHandler
{
    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $options = $event->getArgument('options');
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());

        if (!$options['files'] && !$options['data']) {
            $options['files'] = true;
            $options['data'] = true;
        }
        $backupDir = $remoteSettings['backupsdir'] . '/' . $remoteSettings['time'];
        $dbBackupFile = $this->backupFileName('.sql.gz', $remoteSettings['time']);
        $filesBackupFile = $this->backupFileName('.tar.gz', $remoteSettings['time']);

        $collection = $this->collectionBuilder();
        $collection
            ->taskSsh($remoteConfig->getHost(), $auth)
                ->exec((string) CommandBuilder::create('mkdir')->addFlag('p')->addArgument($backupDir))
            ->taskSFTP($remoteConfig->getHost(), $auth);
        if ($options['files']) {
            $collection->put($backupDir . '/' . $filesBackupFile, $filesBackupFile);
        }
        if ($options['data']) {
            $collection->put($backupDir . '/' . $dbBackupFile, $dbBackupFile);
        }

        return $collection;
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractBackupHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class DownloadBackupHandler extends AbstractBackupHandler
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

        if (!$options['files'] && !$options['data']) {
            $options['files'] = true;
            $options['data'] = true;
        }
        $backupDir = $remoteSettings['backupsdir'] . '/' . $remoteSettings['time'];
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        $collection = $this->collectionBuilder();
        $collection
            ->taskSFTP($remoteConfig->getHost(), $auth);

        // Download files.
        if ($options['files']) {
            $filesBackupFile = $this->backupFileName('.tar.gz', $remoteSettings['time']);
            $collection->get($backupDir . '/' . $filesBackupFile, $filesBackupFile);
        }

        // Download data.
        if ($options['data']) {
            $dbBackupFile = $this->backupFileName('.sql.gz', $remoteSettings['time']);
            $collection->get($backupDir . '/' . $dbBackupFile, $dbBackupFile);
        }

        return $collection;
    }
}

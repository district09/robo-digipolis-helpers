<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractBackupHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class BackupRemoteHandler extends AbstractBackupHandler
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
        $options = $event->getArgument('options');
        $fileBackupConfig = $event->getArgument('fileBackupConfig');
        $timeouts = $event->getArgument('timeouts');

        if (!$options['files'] && !$options['data']) {
            $options['files'] = true;
            $options['data'] = true;
        }

        $backupDir = $remoteSettings['backupsdir'] . '/' . $remoteSettings['time'];
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        $collection = $this->collectionBuilder();

        if ($options['files']) {
            $collection
                ->taskRemoteFilesBackup($remoteConfig->getHost(), $auth, $backupDir, $remoteSettings['filesdir'])
                    ->backupFile($this->backupFileName('.tar.gz'))
                    ->excludeFromBackup($fileBackupConfig['exclude_from_backup'])
                    ->backupSubDirs($fileBackupConfig['file_backup_subdirs'])
                    ->timeout($timeouts['backup_files']);
        }

        if ($options['data']) {
            $collection
                ->taskRemoteDatabaseBackup($remoteConfig->getHost(), $auth, $backupDir, $remoteConfig->getCurrentProjectRoot())
                    ->backupFile($this->backupFileName('.sql'))
                    ->timeout($timeouts['backup_database']);
        }

        return $collection;
    }
}

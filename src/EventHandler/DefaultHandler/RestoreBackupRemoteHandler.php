<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractBackupHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class RestoreBackupRemoteHandler extends AbstractBackupHandler
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
        $timeouts = $event->getArgument('timeouts');

        if (!$options['files'] && !$options['data']) {
            $options['files'] = true;
            $options['data'] = true;
        }

        $backupDir = $remoteSettings['backupsdir'] . '/' . $remoteSettings['time'];

        $collection = $this->collectionBuilder();

        if ($options['files']) {
            $filesBackupFile =  $this->backupFileName('.tar.gz', $remoteSettings['time']);
            $collection
                ->taskSsh($remoteConfig->getHost(), new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile()))
                    ->remoteDirectory($remoteSettings['filesdir'], true)
                    ->timeout($timeouts['restore_files_backup'])
                    ->exec(
                        (string) CommandBuilder::create('tar')
                            ->addFlag('xkz')
                            ->addFlag('f', $backupDir . '/' . $filesBackupFile)
                    );
        }

        // Restore the db backup.
        if ($options['data']) {
            $dbBackupFile =  $this->backupFileName('.sql.gz', $remoteSettings['time']);
            $collection
                ->taskSsh($remoteConfig->getHost(), new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile()))
                    ->remoteDirectory($remoteConfig->getCurrentProjectRoot(), true)
                    ->timeout($timeouts['restore_db_backup'])
                    ->exec(
                        (string) CommandBuilder::create('vendor/bin/robo digipolis:database-restore')
                            ->addOption('source', $backupDir . '/' . $dbBackupFile)
                    );
        }

        return $collection;
    }
}

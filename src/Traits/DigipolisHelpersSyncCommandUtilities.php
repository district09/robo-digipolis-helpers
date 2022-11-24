<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;

trait DigipolisHelpersSyncCommandUtilities
{
    use CustomEventAwareTrait;
    use EventDispatcher;
    use DigipolisHelpersCommandUtilities;
    use CommandWithBackups;

    /**
     * Sync the database and files between two sites.
     *
     * @param string $sourceUser
     *   SSH user to connect to the source server.
     * @param string $sourceHost
     *   IP address of the source server.
     * @param string $sourcePrivateKeyFile
     *   Private key file to use to connect to the source server.
     * @param string $destinationUser
     *   SSH user to connect to the destination server.
     * @param string $destinationHost
     *   IP address of the destination server.
     * @param string $destinationPrivateKeyFile
     *   Private key file to use to connect to the destination server.
     * @param string $sourceApp
     *   The name of the source app we're syncing. Used to determine the
     *   directory to sync.
     * @param string $destinationApp
     *   The name of the destination app we're syncing. Used to determine the
     *   directory to sync to.
     *
     * @return \Robo\Contract\TaskInterface
     *   The sync task.
     */
    public function sync(
        $sourceUser,
        $sourceHost,
        $sourcePrivateKeyFile,
        $destinationUser,
        $destinationHost,
        $destinationPrivateKeyFile,
        $sourceApp = 'default',
        $destinationApp = 'default',
        $opts = ['files' => false, 'data' => false, 'rsync' => true]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }

        $opts['rsync'] = !isset($opts['rsync']) || $opts['rsync'];

        $sourceRemoteSettings = $this->getRemoteSettings(
            $sourceHost,
            $sourceUser,
            $sourcePrivateKeyFile,
            $sourceApp
        );
        $sourceProjectRoot = $this->getCurrentProjectRoot($sourceHost, $sourceUser, $sourcePrivateKeyFile, $sourceRemoteSettings);
        $sourceRemoteConfig = new RemoteConfig($sourceHost, $sourceUser, $sourcePrivateKeyFile, $sourceRemoteSettings, $sourceProjectRoot);

        $destinationRemoteSettings = $this->getRemoteSettings(
            $destinationHost,
            $destinationUser,
            $destinationPrivateKeyFile,
            $destinationApp
        );
        $destinationProjectRoot = $this->getCurrentProjectRoot($destinationHost, $destinationUser, $destinationPrivateKeyFile, $destinationRemoteSettings);
        $destinationRemoteConfig = new RemoteConfig($destinationHost, $destinationUser, $destinationPrivateKeyFile, $destinationRemoteSettings, $destinationProjectRoot);

        $collection = $this->collectionBuilder();

        if ($opts['files'] && $opts['rsync']) {
            // Files are rsync'ed, no need to sync them through backups later.
            $opts['files'] = false;
            $collection->addTask(
                $this->rsyncFilesBetweenHostsTask(
                    $sourceRemoteConfig,
                    $destinationRemoteConfig,
                )
            );
        }

        if ($opts['data'] || $opts['files']) {
            // Create a backup on the source host.
            $collection->addTask(
                $this->backupRemoteTask($sourceRemoteConfig, $opts)
            );
            // Download the backup from the source host to the local machine.
            $collection->addTask(
                $this->downloadBackupTask($sourceRemoteConfig, $opts)
            );
            // Remove the backup from the source host.
            $collection->addTask(
                $this->removeBackupRemoteTask($sourceRemoteConfig, $opts)
            );
            // Upload the backup to the destination host.
            $collection->addTask(
                $this->uploadBackupTask($destinationRemoteConfig, $opts)
            );
            // Restore the backup on the destination host.
            $collection->addTask(
                $this->restoreBackupRemoteTask($destinationRemoteConfig, $opts)
            );
            // Remove the backup from the destination host.
            $collection->completion(
                $this->removeBackupRemoteTask($destinationRemoteConfig, $opts)
            );

            // Finally remove the local backups.
            $collection->completion($this->removeLocalBackupTask($sourceRemoteConfig, $opts));
        }

        $collection->completion($this->clearCacheTask($destinationRemoteConfig));

        return $collection;
    }

    /**
     * Get the task that rsyncs files between hosts.
     *
     * @param RemoteConfig $sourceRemoteConfig
     *   RemoteConfig object populated with data relevant to the source.
     * @param RemoteConfig $destinationRemoteConfig
     *   RemoteConfig object populated with data relevant to the destination.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function rsyncFilesBetweenHostsTask(
        RemoteConfig $sourceRemoteConfig,
        RemoteConfig $destinationRemoteConfig
    ) {
        return $this->handleTaskEvent(
            'digipolis:rsync-files-between-hosts',
            [
                'sourceRemoteConfig' => $sourceRemoteConfig,
                'destinationRemoteConfig' => $destinationRemoteConfig,
                'fileBackupConfig' => $this->getFileBackupConfig(),
                'timeouts' => [
                    'synctask_rsync' => $this->getTimeoutSetting('synctask_rsync')
                ]
            ]
        );
    }

    /**
     * Remove the local backup of a remote application.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param array $options
     *   Options that were used to create the backup.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function removeLocalBackupTask(RemoteConfig $remoteConfig, $options)
    {
        return $this->handleTaskEvent(
            'digipolis:remove-local-backup',
            [
                'remoteConfig' => $remoteConfig,
                'fileBackupConfig' => $this->getFileBackupConfig(),
                'options' => $options,
            ]
        );
    }
}

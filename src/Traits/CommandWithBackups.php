<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\Robo\Helpers\Util\RemoteConfig;

trait CommandWithBackups
{

    /**
     * Get the task that will create a backup on the host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param array $backupOpts
     *   Extra options for creating the backup.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function backupRemoteTask(RemoteConfig $remoteConfig, $backupOpts)
    {
        return $this->handleTaskEvent(
            'digipolis:backup-remote',
            [
                'remoteConfig' => $remoteConfig,
                'fileBackupConfig' => $this->getFileBackupConfig(),
                'options' => $backupOpts,
                'timeouts' => [
                    'backup_files' => $this->getTimeoutSetting('backup_files'),
                    'backup_database' => $this->getTimeoutSetting('backup_database'),
                ],
            ]
        );
    }

    /**
     * Get the task that will execute tasks before restoring a backup.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param array $backupOpts
     *   Extra options for restoring the backup.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function preRestoreBackupRemoteTask(RemoteConfig $remoteConfig, $backupOpts)
    {
        return $this->handleTaskEvent(
            'digipolis:pre-restore-backup-remote',
            [
                'remoteConfig' => $remoteConfig,
                'fileBackupConfig' => $this->getFileBackupConfig(),
                'options' => $backupOpts,
                'timeouts' => [
                    'pre_restore' => $this->getTimeoutSetting('pre_restore'),
                ],
            ]
        );
    }

    /**
     * Get the task that will restore a backup.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param array $backupOpts
     *   Extra options for restoring the backup.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function restoreBackupRemoteTask(RemoteConfig $remoteConfig, $backupOpts)
    {
        return $this->handleTaskEvent(
            'digipolis:restore-backup-remote',
            [
                'remoteConfig' => $remoteConfig,
                'fileBackupConfig' => $this->getFileBackupConfig(),
                'options' => $backupOpts,
                'timeouts' => [
                    'restore_files_backup' => $this->getTimeoutSetting('restore_files_backup'),
                    'restore_db_backup' => $this->getTimeoutSetting('restore_db_backup'),
                ],
            ]
        );
    }

    /**
     * Get the task that will run after syncing remotes.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param array $opts
     *   Extra options passed to the sync command.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function postSyncRemoteTask(RemoteConfig $remoteConfig, $opts)
    {
        return $this->handleTaskEvent(
            'digipolis:post-sync-remote',
            [
                'remoteConfig' => $remoteConfig,
                'fileBackupConfig' => $this->getFileBackupConfig(),
                'options' => $opts,
            ]
        );
    }

    /**
     * Get the task that will download a backup from a host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param array $backupOpts
     *   Extra options that were used for creating the backup.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function downloadBackupTask(RemoteConfig $remoteConfig, $backupOpts)
    {
        return $this->handleTaskEvent(
            'digipolis:download-backup',
            [
                'remoteConfig' => $remoteConfig,
                'options' => $backupOpts,
            ]
        );
    }

    /**
     * Get the task that will upload a backup to a host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param array $backupOpts
     *   Extra options that were used for creating the backup.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function uploadBackupTask(RemoteConfig $remoteConfig, $backupOpts)
    {
        return $this->handleTaskEvent(
            'digipolis:upload-backup',
            [
                'remoteConfig' => $remoteConfig,
                'options' => $backupOpts,
            ]
        );
    }

    /**
     * Get the task that will remove a backup from a host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param array $backupOpts
     *   Extra options that were used for creating the backup.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function removeBackupRemoteTask(RemoteConfig $remoteConfig, $backupOpts)
    {
        return $this->handleTaskEvent(
            'digipolis:remove-backup-remote',
            [
                'remoteConfig' => $remoteConfig,
                'options' => $backupOpts,
                'timeouts' => [
                    'remove_backup' => $this->getTimeoutSetting('remove_backup'),
                ],
            ]
        );
    }
}

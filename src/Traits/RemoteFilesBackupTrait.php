<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\Robo\Helpers\Robo\Plugin\Tasks\RemoteFilesBackup;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

trait RemoteFilesBackupTrait
{

    /**
     * Creates a RemoteDatabaseBackup task.
     *
     * @param string $host
     *   The host to create the backup on.
     * @param AbstractAuth $auth
     *   The authentication to use to connect to the host.
     * @param string $backupDir
     *   The directory to save the backup to (on the remote host).
     * @param string $cwd
     *   The working directory to execute the commands in.
     *
     * @return \DigipolisGent\Robo\Helpers\Tasks\RemoteFilesBackup
     */
    protected function taskRemoteFilesBackup($host, AbstractAuth $auth, $backupDir, $cwd)
    {
        return $this->task(RemoteFilesBackup::class, $host, $auth, $backupDir, $cwd);
    }
}

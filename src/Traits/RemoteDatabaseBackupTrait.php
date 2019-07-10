<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\Robo\Helpers\Robo\Plugins\Tasks\RemoteDatabaseBackup;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;


trait RemoteDatabaseBackupTrait
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
     * @return RemoteDatabaseBackup
     */
    protected function taskRemoteDatabaseBackup($host, AbstractAuth $auth, $backupDir, $cwd)
    {
        return $this->task(RemoteDatabaseBackup::class, $host, $auth, $backupDir, $cwd);
    }
}

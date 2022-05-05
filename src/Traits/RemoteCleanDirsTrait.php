<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\Robo\Helpers\Robo\Plugin\Tasks\RemoteCleanDirs;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;


trait RemoteCleanDirsTrait
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
     * @param string $directory
     *   The working directory to clean.
     * @param string $limit
     *   The number of items to keep.
     *
     * @return RemoteCleanDirs
     */
    protected function taskRemoteCleanDirs($host, AbstractAuth $auth, $cwd, $directory, $limit = false)
    {
        return $this->task(RemoteCleanDirs::class, $host, $auth, $cwd, $directory, $limit);
    }
}

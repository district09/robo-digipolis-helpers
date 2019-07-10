<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\Robo\Helpers\Robo\Plugins\Tasks\RemoteRemoveRelease;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

trait RemoteRemoveReleaseTrait
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
     * @param string $releaseDirectory
     *   The release directory to remove.
     *
     * @return RemoteRemoveRelease
     */
    protected function taskRemoteRemoveRelease($host, AbstractAuth $auth, $cwd, $releaseDirectory)
    {
        return $this->task(RemoteRemoveRelease::class, $host, $auth, $cwd, $releaseDirectory);
    }
}

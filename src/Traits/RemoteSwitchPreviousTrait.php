<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\Robo\Helpers\Robo\Plugins\Tasks\RemoteSwitchPrevious;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

trait RemoteSwitchPreviousTrait
{

    /**
     * Creates a new RemoteSwitchPrevious task.
     *
     * @param string $host
     *   The host to create the backup on.
     * @param AbstractAuth $auth
     *   The authentication to use to connect to the host.
     * @param string $cwd
     *   The working directory to execute the commands in.
     * @param string $releasesDir
     *   The releases directory.
     * @param string $currentSymlink
     *   The current release directory.
     *
     * @return RemoteSwitchPrevious
     */
    protected function taskRemoteSwitchPrevious($host, AbstractAuth $auth, $cwd, $releasesDir, $currentSymlink)
    {
        return $this->task(RemoteSwitchPrevious::class, $host, $auth, $cwd, $releasesDir, $currentSymlink);
    }
}

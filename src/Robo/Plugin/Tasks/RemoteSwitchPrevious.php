<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Tasks;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

class RemoteSwitchPrevious extends Remote
{
    /**
     * The releases directory.
     *
     * @var string
     */
    protected $releasesDir;

    /**
     * The current release directory.
     *
     * @var string
     */
    protected $currentSymlink;

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
     */
    public function __construct($host, AbstractAuth $auth, $cwd, $releasesDir, $currentSymlink)
    {
        parent::__construct($host, $auth, $cwd);
        $this->releasesDir = $releasesDir;
        $this->currentSymlink = $currentSymlink;
    }


    /**
     * {@inheritdoc}
     */
    protected function getCommand(): CommandBuilder
    {
        return CommandBuilder::create('vendor/bin/robo digipolis:switch-previous')
            ->addArgument($this->releasesDir)
            ->addArgument($this->currentSymlink);

    }
}

<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Tasks;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

class RemoteRemoveRelease extends Remote
{
    protected $releaseDirectory;

    /**
     * Creates a new Remote task.
     *
     * @param string $host
     *   The host to create the backup on.
     * @param AbstractAuth $auth
     *   The authentication to use to connect to the host.
     * @param string $cwd
     *   The working directory to execute the commands in.
     * @param string $releaseDirectory
     *   The release directory to remove.
     */
    public function __construct($host, AbstractAuth $auth, $cwd, $releaseDirectory)
    {
        parent::__construct($host, $auth, $cwd);
        $this->releaseDirectory = $releaseDirectory;
    }

    /**
     * Get the command to run the backup over ssh.
     *
     * @return CommandBuilder
     */
    protected function getCommand(): CommandBuilder
    {
        return CommandBuilder::create('chown')
            ->addFlag('R')
            ->addArgument($this->auth->getUser() . ':' . $this->auth->getUser())
            ->addArgument($this->releaseDirectory)
            ->onSuccess(CommandBuilder::create('chmod')
                ->addFlag('R')
                ->addArgument('a+rwx')
                ->addArgument($this->releaseDirectory)
                ->onSuccess(CommandBuilder::create('rm')
                    ->addFlag('rf')
                    ->addArgument($this->releaseDirectory)
                )
            );
    }
}

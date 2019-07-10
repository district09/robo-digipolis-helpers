<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugins\Tasks;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

class RemoteCleanDirs extends Remote
{
    protected $directory;

    protected $limit;

    public function __construct($host, AbstractAuth $auth, $cwd, $directory, $limit = false)
    {
        parent::__construct($host, $auth, $cwd);
        $this->directory = $directory;
        $this->limit = $limit;
    }

    /**
     * Get the command to run the backup over ssh.
     *
     * @return CommandBuilder
     */
    protected function getCommand(): CommandBuilder
    {
        return CommandBuilder::create('vendor/bin/robo digipolis:clean-dir')
            ->addArgument($this->directory . ($this->limit ? ':' . ($this->limit + 1) : ''));
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Tasks;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Task\Deploy\Ssh;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

abstract class RemoteBackup extends Remote
{
    /**
     * Directory to save the backup in.
     *
     * @var string
     */
    protected $backupDir;

    /**
     * Name of the backup file.
     *
     * @var string
     */
    protected $backupFile;

    /**
     * Creates a new RemoteBackup task.
     *
     * @param string $host
     *   The host to create the backup on.
     * @param AbstractAuth $auth
     *   The authentication to use to connect to the host.
     * @param string $backupDir
     *   The directory to save the backup to (on the remote host).
     * @param string $cwd
     *   The working directory to execute the commands in.
     */
    public function __construct($host, AbstractAuth $auth, $backupDir, $cwd)
    {
        parent::__construct($host, $auth, $cwd);
        $this->backupDir = $backupDir;
    }

    /**
     * Set the backup file name.
     *
     * @param string $backupFile
     *
     * @return $this
     */
    public function backupFile($backupFile = 'backup.tar.gz')
    {
        $this->backupFile = $backupFile;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $command = CommandBuilder::create('mkdir')
            ->addFlag('p')
            ->addArgument($this->backupDir)
            ->onSuccess($this->getCommand());

        return $this->collectionBuilder()->taskSsh($this->host, $this->auth)
            ->remoteDirectory($this->cwd)
            ->timeout($this->timeout)
            ->exec((string) $command)
            ->run();
    }
}

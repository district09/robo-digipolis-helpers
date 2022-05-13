<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Tasks;

use DigipolisGent\CommandBuilder\CommandBuilder;

class RemoteFilesBackup extends RemoteBackup
{
    /**
     * Name of the backup file.
     *
     * @var string
     */
    protected $backupFile = 'backup.tar.gz';

    /**
     * Files or directories to exclude from the backup.
     *
     * @var string[]
     */
    protected $excludeFromBackup = [];

    /**
     * File backup subdirs.
     *
     * @var string[]
     */
    protected $fileBackupSubDirs = [];

    /**
     * Exclude files or folders form the backup.
     *
     * @param array $excludeFromBackup
     *
     * @return $this
     */
    public function excludeFromBackup($excludeFromBackup = [])
    {
        $this->excludeFromBackup = $excludeFromBackup;
        return $this;
    }

    /**
     * The subdirectories of the filesdir that need to be backed up.
     *
     * @param array $fileBackupSubDirs
     *
     * @return $this
     */
    public function backupSubDirs($fileBackupSubDirs = [])
    {
        $this->backupSubDirs = $fileBackupSubDirs;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommand(): CommandBuilder
    {
        $command = CommandBuilder::create('tar')
            ->addFlag('pczh')
            ->addFlag('f', $this->backupDir . DIRECTORY_SEPARATOR . $this->backupFile)
            ->addFlag('C', $this->cwd);
        foreach ($this->excludeFromBackup as $exclude) {
            $command->addOption('exclude', $exclude);
        }
        $arguments = $this->fileBackupSubDirs ?: ['*'];
        foreach ($arguments as $argument) {
            $command->addArgument($argument);
        }

        return $command;

    }
}

<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Tasks;

use DigipolisGent\CommandBuilder\CommandBuilder;

class RemoteDatabaseBackup extends RemoteBackup
{
    /**
     * Name of the backup file.
     *
     * @var string
     */
    protected $backupFile = 'backup.sql';

    /**
     * {@inheritdoc}
     */
    protected function getCommand(): CommandBuilder
    {
        return CommandBuilder::create('vendor/bin/robo digipolis:database-backup')
            ->addOption('destination', $this->backupDir . DIRECTORY_SEPARATOR . $this->backupFile);
    }
}

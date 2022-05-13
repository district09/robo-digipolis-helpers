<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Backup;

interface BackupTaskFactoryAwareInterface
{
    public function setBackupTaskFactory(Backup $backupTaskFactory);
}

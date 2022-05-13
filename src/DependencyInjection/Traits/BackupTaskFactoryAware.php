<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection\Traits;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Backup;

trait BackupTaskFactoryAware
{
    protected Backup $backupTaskFactory;

    public function setBackupTaskFactory(Backup $backupTaskFactory)
    {
        $this->backupTaskFactory = $backupTaskFactory;
    }
}

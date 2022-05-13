<?php

namespace DigipolisGent\Robo\Helpers\Util\TaskFactory;

trait BackupConfigTrait {

  protected function getBackupConfig()
  {
      $handlers = $this->getCustomEventHandlers('digipolis-backup-config');
      $backupConfig = [
          'file_backup_subdirs' => [],
          'exclude_from_backup' => [],
      ];
      foreach ($handlers as $handler) {
          $handlerConfig = $handler();
          if (isset($handlerConfig['file_backup_subdirs'])) {
              $backupConfig['file_backup_subdirs'] = array_merge($backupConfig['file_backup_subdirs'], $handlerConfig['file_backup_subdirs']);
          }

          if (isset($handlerConfig['exclude_from_backup'])) {
              $backupConfig['exclude_from_backup'] = array_merge($backupConfig['exclude_from_backup'], $handlerConfig['exclude_from_backup']);
          }
      }
  }

}

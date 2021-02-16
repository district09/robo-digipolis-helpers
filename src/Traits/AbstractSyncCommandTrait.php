<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

trait AbstractSyncCommandTrait
{
    /**
     * @see \DigipolisGent\Robo\Helpers\Traits\TraitDependencyCheckerTrait
     */
    protected function getAbstractSyncCommandTraitDependencies()
    {
        return [AbstractCommandTrait::class];
    }

    /**
     * Download a backup of files (storage folder) and database.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The download backup task.
     */
    protected function downloadBackupTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $opts = ['files' => false, 'data' => false]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }
        $backupDir = $remote['backupsdir'] . '/' . $remote['time'];

        $collection = $this->collectionBuilder();
        $collection
            ->taskSFTP($worker, $auth);

        // Download files.
        if ($opts['files']) {
            $filesBackupFile = $this->backupFileName('.tar.gz', $remote['time']);
            $collection->get($backupDir . '/' . $filesBackupFile, $filesBackupFile);
        }

        // Download data.
        if ($opts['data']) {
            $dbBackupFile = $this->backupFileName('.sql.gz', $remote['time']);
            $collection->get($backupDir . '/' . $dbBackupFile, $dbBackupFile);
        }
        return $collection;
    }

    /**
     * Upload a backup of files (storage folder) and database to a server.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The upload backup task.
     */
    protected function uploadBackupTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $opts = ['files' => false, 'data' => false]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }
        $backupDir = $remote['backupsdir'] . '/' . $remote['time'];
        $dbBackupFile = $this->backupFileName('.sql.gz', $remote['time']);
        $filesBackupFile = $this->backupFileName('.tar.gz', $remote['time']);

        $collection = $this->collectionBuilder();
        $collection
            ->taskSsh($worker, $auth)
                ->exec((string) CommandBuilder::create('mkdir')->addFlag('p')->addArgument($backupDir))
            ->taskSFTP($worker, $auth);
        if ($opts['files']) {
            $collection->put($backupDir . '/' . $filesBackupFile, $filesBackupFile);
        }
        if ($opts['data']) {
            $collection->put($backupDir . '/' . $dbBackupFile, $dbBackupFile);
        }
        return $collection;
    }
}

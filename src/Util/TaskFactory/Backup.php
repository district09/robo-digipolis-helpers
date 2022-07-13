<?php

namespace DigipolisGent\Robo\Helpers\Util\TaskFactory;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\DependencyInjection\RemoteHelperAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\RemoteHelperAware;
use DigipolisGent\Robo\Helpers\Util\RemoteHelper;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;

class Backup implements BuilderAwareInterface, RemoteHelperAwareInterface, CustomEventAwareInterface
{
    use TaskAccessor;
    use \Robo\Task\Base\Tasks;
    use \DigipolisGent\Robo\Task\Deploy\Tasks;
    use RemoteHelperAware;
    use CustomEventAwareTrait;
    use BackupConfigTrait;
    use \DigipolisGent\Robo\Helpers\Traits\RemoteDatabaseBackupTrait;
    use \DigipolisGent\Robo\Helpers\Traits\RemoteFilesBackupTrait;

    public function __construct(RemoteHelper $remoteHelper)
    {
        $this->setRemoteHelper($remoteHelper);
    }

    public static function create(DefinitionContainerInterface $container)
    {
        $object = new static(
            $container->get(RemoteHelper::class)
        );
        $object->setBuilder(CollectionBuilder::create($container, $object));

        return $object;
    }

    /**
     * Create a backup of files (storage folder) and database.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The backup task.
     */
    public function backupTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $opts = ['files' => false, 'data' => false]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }
        $backupConfig = $this->getBackupConfig();
        $backupDir = $remote['backupsdir'] . '/' . $remote['time'];
        $collection = $this->collectionBuilder();
        if ($opts['files']) {
            $collection
                ->taskRemoteFilesBackup($worker, $auth, $backupDir, $remote['filesdir'])
                    ->backupFile($this->backupFileName('.tar.gz'))
                    ->excludeFromBackup($backupConfig['exclude_from_backup'])
                    ->backupSubDirs($backupConfig['file_backup_subdirs'])
                    ->timeout($this->remoteHelper->getTimeoutSetting('backup_files'));
        }

        if ($opts['data']) {
            $currentProjectRoot = $this->remoteHelper->getCurrentProjectRoot($worker, $auth, $remote);
            $collection
                ->taskRemoteDatabaseBackup($worker, $auth, $backupDir, $currentProjectRoot)
                    ->backupFile($this->backupFileName('.sql'))
                    ->timeout($this->remoteHelper->getTimeoutSetting('backup_database'));
        }
        return $collection;
    }

    /**
     * Restore a backup of files (storage folder) and database.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The restore backup task.
     */
    public function restoreBackupTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $opts = ['files' => false, 'data' => false]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }

        $currentProjectRoot = $this->remoteHelper->getCurrentProjectRoot($worker, $auth, $remote);
        $backupDir = $remote['backupsdir'] . '/' . $remote['time'];

        $collection = $this->collectionBuilder();

        // Restore the files backup.
        $preRestoreBackup = $this->preRestoreBackupTask($worker, $auth, $remote, $opts);
        if ($preRestoreBackup) {
            $collection->addTask($preRestoreBackup);
        }

        if ($opts['files']) {
            $filesBackupFile =  $this->backupFileName('.tar.gz', $remote['time']);
            $collection
                ->taskSsh($worker, $auth)
                    ->remoteDirectory($remote['filesdir'], true)
                    ->timeout($this->remoteHelper->getTimeoutSetting('restore_files_backup'))
                    ->exec(
                        (string) CommandBuilder::create('tar')
                            ->addFlag('xkz')
                            ->addFlag('f', $backupDir . '/' . $filesBackupFile)
                    );
        }

        // Restore the db backup.
        if ($opts['data']) {
            $dbBackupFile =  $this->backupFileName('.sql.gz', $remote['time']);
            $collection
                ->taskSsh($worker, $auth)
                    ->remoteDirectory($currentProjectRoot, true)
                    ->timeout($this->remoteHelper->getTimeoutSetting('restore_db_backup'))
                    ->exec(
                        (string) CommandBuilder::create('vendor/bin/robo digipolis:database-restore')
                            ->addOption('source', $backupDir . '/' . $dbBackupFile)
                    );
        }
        return $collection;
    }


    /**
     * Pre restore backup task.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return bool|\Robo\Contract\TaskInterface
     *   The pre restore backup task, false if no pre restore backup tasks need
     *   to run.
     */
    protected function preRestoreBackupTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $opts = ['files' => false, 'data' => false]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }
        if ($opts['files']) {
            $backupConfig = $this->getBackupConfig();
            $removeFiles = CommandBuilder::create('rm')->addFlag('rf');
            if (!$backupConfig['file_backup_subdirs']) {
                $removeFiles->addArgument('./*');
                $removeFiles->addArgument('./.??*');
            }
            foreach ($backupConfig['file_backup_subdirs'] as $subdir) {
                $removeFiles->addArgument($subdir . '/*');
                $removeFiles->addArgument($subdir . '/.??*');
            }

            return $this->taskSsh($worker, $auth)
                ->remoteDirectory($remote['filesdir'], true)
                // Files dir can be pretty big on large sites.
                ->timeout($this->remoteHelper->getTimeoutSetting('pre_restore_remove_files'))
                ->exec((string) $removeFiles);
        }

        return false;
    }

    /**
     * Remove a backup.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The backup task.
     */
    public function removeBackupTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $opts = ['files' => false, 'data' => false]
    ) {
        $backupDir = $remote['backupsdir'] . '/' . $remote['time'];

        $collection = $this->collectionBuilder();
        $collection->taskSsh($worker, $auth)
            ->timeout($this->remoteHelper->getTimeoutSetting('remove_backup'))
            ->exec(
                (string) CommandBuilder::create('rm')
                    ->addFlag('rf')
                    ->addArgument($backupDir)
            );

        return $collection;
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
    public function downloadBackupTask(
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
    public function uploadBackupTask(
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

    /**
     * Generate a backup filename based on the given time.
     *
     * @param string $extension
     *   The extension to append to the filename. Must include leading dot.
     * @param int|null $timestamp
     *   The timestamp to generate the backup name from. Defaults to the request
     *   time.
     *
     * @return string
     *   The generated filename.
     */
    public function backupFileName($extension, $timestamp = null)
    {
        if (is_null($timestamp)) {
            $timestamp = $this->remoteHelper->getTime();
        }
        return $timestamp . '_' . date('Y_m_d_H_i_s', $timestamp) . $extension;
    }
}

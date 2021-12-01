<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;

trait AbstractSyncRemoteCommandTrait
{
    /**
     * @see \DigipolisGent\Robo\Helpers\Traits\TraitDependencyCheckerTrait
     */
    protected function getAbstractSyncRemoteCommandTraitDependencies()
    {
        return [AbstractSyncCommandTrait::class];
    }

    /**
     * Sync the database and files between two sites.
     *
     * @param string $sourceUser
     *   SSH user to connect to the source server.
     * @param string $sourceHost
     *   IP address of the source server.
     * @param string $sourceKeyFile
     *   Private key file to use to connect to the source server.
     * @param string $destinationUser
     *   SSH user to connect to the destination server.
     * @param string $destinationHost
     *   IP address of the destination server.
     * @param string $destinationKeyFile
     *   Private key file to use to connect to the destination server.
     * @param string $sourceApp
     *   The name of the source app we're syncing. Used to determine the
     *   directory to sync.
     * @param string $destinationApp
     *   The name of the destination app we're syncing. Used to determine the
     *   directory to sync to.
     *
     * @return \Robo\Contract\TaskInterface
     *   The sync task.
     */
    protected function sync(
        $sourceUser,
        $sourceHost,
        $sourceKeyFile,
        $destinationUser,
        $destinationHost,
        $destinationKeyFile,
        $sourceApp = 'default',
        $destinationApp = 'default',
        $opts = ['files' => false, 'data' => false, 'rsync' => true]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }

        $opts['rsync'] = !isset($opts['rsync']) || $opts['rsync'];

        $sourceRemote = $this->getRemoteSettings(
            $sourceHost,
            $sourceUser,
            $sourceKeyFile,
            $sourceApp
        );
        $sourceAuth = new KeyFile($sourceUser, $sourceKeyFile);

        $destinationRemote = $this->getRemoteSettings(
            $destinationHost,
            $destinationUser,
            $destinationKeyFile,
            $destinationApp
        );
        $destinationAuth = new KeyFile($destinationUser, $destinationKeyFile);

        $collection = $this->collectionBuilder();

        if ($opts['files'] && $opts['rsync']) {
            // Files are rsync'ed, no need to sync them through backups later.
            $opts['files'] = false;
            $collection->addTask(
                $this->rsyncAllFiles(
                    $sourceAuth,
                    $sourceHost,
                    $sourceKeyFile,
                    $sourceRemote,
                    $destinationAuth,
                    $destinationHost,
                    $destinationKeyFile,
                    $destinationRemote
                )
            );
        }

        if ($opts['data'] || $opts['files']) {
            // Create a backup on the source host.
            $collection->addTask(
                $this->backupTask(
                    $sourceHost,
                    $sourceAuth,
                    $sourceRemote,
                    $opts
                )
            );
            // Download the backup from the source host to the local machine.
            $collection->addTask(
                $this->downloadBackupTask(
                    $sourceHost,
                    $sourceAuth,
                    $sourceRemote,
                    $opts
                )
            );
            // Remove the backup from the source host.
            $collection->addTask(
                $this->removeBackupTask(
                    $sourceHost,
                    $sourceAuth,
                    $sourceRemote,
                    $opts
                )
            );
            // Upload the backup to the destination host.
            $collection->addTask(
                $this->uploadBackupTask(
                    $destinationHost,
                    $destinationAuth,
                    $destinationRemote,
                    $opts
                )
            );
            // Restore the backup on the destination host.
            $collection->addTask(
                $this->restoreBackupTask(
                    $destinationHost,
                    $destinationAuth,
                    $destinationRemote,
                    $opts
                )
            );
            // Remove the backup from the destination host.
            $collection->completion(
                $this->removeBackupTask(
                    $destinationHost,
                    $destinationAuth,
                    $destinationRemote,
                    $opts
                )
            );

            // Finally remove the local backups.
            $dbBackupFile = $this->backupFileName('.sql.gz', $sourceRemote['time']);
            $removeLocalBackup = CommandBuilder::create('rm')
                ->addFlag('f')
                ->addArgument($dbBackupFile);
            if ($opts['files']) {
                $removeLocalBackup->addArgument($this->backupFileName('.tar.gz', $sourceRemote['time']));
            }

            $collection->completion(
                $this->taskExecStack()
                    ->exec((string) $removeLocalBackup)
            );
        }

        if ($clearCache = $this->clearCacheTask($destinationHost, $destinationAuth, $destinationRemote)) {
            $collection->completion($clearCache);
        }

        return $collection;
    }

    protected function rsyncAllFiles(
        AbstractAuth $sourceAuth,
        $sourceHost,
        $sourceKeyFile,
        $sourceRemote,
        AbstractAuth $destinationAuth,
        $destinationHost,
        $destinationKeyFile,
        $destinationRemote
    ) {
        $tmpKeyFile = '~/.ssh/' . uniqid('robo_', true) . '.id_rsa';
        $destinationUser = $destinationAuth->getUser();
        $sourceUser = $sourceAuth->getUser();
        $collection = $this->collectionBuilder();
        // Generate a temporary key.
        $collection->addTask(
            $this->generateKeyPair($tmpKeyFile)
        );

        $collection->completion(
            $this->removeKeyPair($tmpKeyFile)
        );

        // Install it on the destination host.
        $collection->addTask(
            $this->installPublicKeyOnDestination(
                $tmpKeyFile,
                $destinationUser,
                $destinationHost,
                $destinationKeyFile
            )
        );

        // Remove it from the destination host when we're done.
        $collection->completion(
            $this->removePublicKeyFromDestination(
                $tmpKeyFile,
                $destinationHost,
                $destinationAuth
            )
        );

        // Install the private key on the source host.
        $collection->addTask(
            $this->installPrivateKeyOnSource(
                $tmpKeyFile,
                $sourceHost,
                $sourceUser,
                $sourceKeyFile
            )
        );

        // Remove the private key from the source host.
        $collection->completion(
            $this->removePrivateKeyFromSource(
                $tmpKeyFile,
                $sourceHost,
                $sourceAuth
            )
        );

        $dirs = ($this->fileBackupSubDirs ? $this->fileBackupSubDirs : ['']);

        foreach ($dirs as $dir) {
            $dir .= ($dir !== '' ? '/' : '');
            $collection->addTask(
                $this->rsyncDirectory(
                    $dir,
                    $tmpKeyFile,
                    $sourceHost,
                    $sourceAuth,
                    $sourceRemote,
                    $destinationHost,
                    $destinationAuth,
                    $destinationRemote
                )
            );
        }

        return $collection;
    }

    protected function generateKeyPair($privateKey)
    {
        return $this->taskExec(
            (string) CommandBuilder::create('ssh-keygen')
                ->addFlag('q')
                ->addFlag('t', 'rsa')
                ->addFlag('b', 4096)
                ->addRawFlag('N', '""')
                ->addRawFlag('f', $privateKey)
                ->addFlag('C', 'robo:' . md5($privateKey))
        );
    }

    protected function removeKeyPair($privateKey)
    {
        return $this->taskExecStack()
            ->exec(
                (string) CommandBuilder::create('rm')
                    ->addFlag('f')
                    ->addRawArgument($privateKey)
                    ->addRawArgument($privateKey . '.pub')
            );
    }

    protected function installPublicKeyOnDestination($privateKey, $destinationUser, $destinationHost, $destinationKeyFile)
    {
        return $this->taskExec(
            (string) CommandBuilder::create('cat')
                ->addRawArgument($privateKey . '.pub')
                ->pipeOutputTo(
                    CommandBuilder::create('ssh')
                        ->addArgument($destinationUser . '@' . $destinationHost)
                        ->addFlag('o', 'StrictHostKeyChecking=no')
                        ->addRawFlag('i', $destinationKeyFile)
                )
                ->addArgument(
                    CommandBuilder::create('mkdir')
                        ->addFlag('p')
                        ->addRawArgument('~/.ssh')
                        ->onSuccess(
                            CommandBuilder::create('cat')
                                ->chain('~/.ssh/authorized_keys', '>>')
                        )
                )
        );
    }

    protected function removePublicKeyFromDestination($privateKey, $destinationHost, AbstractAuth $destinationAuth)
    {
        return $this->taskSsh($destinationHost, $destinationAuth)
            ->exec(
                (string) CommandBuilder::create('sed')
                    ->addFlag('i', '/robo:' . md5($privateKey) . '/d')
                    ->addRawArgument('~/.ssh/authorized_keys')
            );
    }

    protected function installPrivateKeyOnSource($privateKey, $sourceHost, $sourceUser, $sourceKeyFile)
    {
        return $this->taskRsync()
            ->rawArg('--rsh "ssh -o StrictHostKeyChecking=no -i `vendor/bin/robo digipolis:realpath ' . $sourceKeyFile . '`"')
            ->fromPath($privateKey)
            ->toHost($sourceHost)
            ->toUser($sourceUser)
            ->toPath('~/.ssh')
            ->archive()
            ->compress()
            ->checksum()
            ->wholeFile();
    }

    protected function removePrivateKeyFromSource($privateKey, $sourceHost, AbstractAuth $sourceAuth)
    {
        return $this->taskSsh($sourceHost, $sourceAuth)
            ->exec(
                (string) CommandBuilder::create('rm')
                    ->addFlag('f')
                    ->addRawArgument($privateKey)
            );
    }

    protected function rsyncDirectory($dir, $privateKey, $sourceHost, AbstractAuth $sourceAuth, $sourceSettings, $destinationHost, AbstractAuth $destinationAuth, $destinationSettings)
    {
        $rsync = $this->taskRsync()
            ->rawArg('--rsh "ssh -o StrictHostKeyChecking=no -i `cd -P ' . $sourceSettings['currentdir'] . '/.. && vendor/bin/robo digipolis:realpath ' . $privateKey . '`"')
            ->fromPath($sourceSettings['filesdir'] . '/' . $dir)
            ->toHost($destinationHost)
            ->toUser($destinationAuth->getUser())
            ->toPath($destinationSettings['filesdir'] . '/' . $dir)
            ->archive()
            ->delete()
            ->rawArg('--copy-links --keep-dirlinks')
            ->compress()
            ->checksum()
            ->wholeFile();

        foreach ($this->excludeFromBackup as $exclude) {
            $rsync->exclude($exclude);
        }

        return $this->taskSsh($sourceHost, $sourceAuth)
            ->timeout($this->getTimeoutSetting('synctask_rsync'))
            ->exec($rsync);
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;

trait DigipolisSyncLocalCommandTrait
{
    /**
     * @see \DigipolisGent\Robo\Helpers\Traits\TraitDependencyCheckerTrait
     */
    protected function getDigipolisSyncLocalCommandTraitDependencies()
    {
        return [AbstractSyncCommandTrait::class];
    }

    /**
     * Sync the database and files to your local environment.
     *
     * @param string $host
     *   IP address of the source server.
     * @param string $user
     *   SSH user to connect to the source server.
     * @param string $keyFile
     *   Private key file to use to connect to the source server.
     * @param array $opts
     *   Command options
     *
     * @option app The name of the app we're syncing.
     * @option files Sync only files.
     * @option data Sync only the database.
     * @option rsync Sync the files via rsync.
     *
     * @return \Robo\Contract\TaskInterface
     *   The sync task.
     */
    public function digipolisSyncLocal(
        $host,
        $user,
        $keyFile,
        $opts = [
            'app' => 'default',
            'files' => false,
            'data' => false,
            'rsync' => true,
        ]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }

        $opts['rsync'] = !isset($opts['rsync']) || $opts['rsync'];

        $remote = $this->getRemoteSettings($host, $user, $keyFile, $opts['app']);
        $local = $this->getLocalSettings($opts['app']);
        $auth = new KeyFile($user, $keyFile);
        $collection = $this->collectionBuilder();

        if ($opts['files']) {
            $collection
                ->taskExecStack()
                    ->exec(
                        (string) CommandBuilder::create('chown')
                            ->addFlag('R')
                            ->addRawArgument('$USER')
                            ->addArgument(dirname($local['filesdir']))
                    )
                    ->exec(
                        (string) CommandBuilder::create('chmod')
                            ->addFlag('R')
                            ->addArgument('u+w')
                            ->addArgument(dirname($local['filesdir']))
                    );

            if ($opts['rsync']) {
                $opts['files'] = false;

                $dirs = ($this->fileBackupSubDirs ? $this->fileBackupSubDirs : ['']);

                foreach ($dirs as $dir) {
                    $dir .= ($dir !== '' ? '/' : '');

                    $rsync = $this->taskRsync()
                        ->rawArg('--rsh "ssh -o StrictHostKeyChecking=no -i `vendor/bin/robo digipolis:realpath ' . $keyFile . '`"')
                        ->fromHost($host)
                        ->fromUser($user)
                        ->fromPath($remote['filesdir'] . '/' . $dir)
                        ->toPath($local['filesdir'] . '/' . $dir)
                        ->archive()
                        ->delete()
                        ->rawArg('--copy-links --keep-dirlinks')
                        ->compress()
                        ->checksum()
                        ->wholeFile();

                    foreach ($this->excludeFromBackup as $exclude) {
                        $rsync->exclude($exclude);
                    }

                    $collection->addTask($rsync);
                }
            }
        }

        if ($opts['data'] || $opts['files']) {
            // Create a backup.
            $collection->addTask(
                $this->backupTask(
                    $host,
                    $auth,
                    $remote,
                    $opts
                )
            );
            // Download the backup.
            $collection->addTask(
                $this->downloadBackupTask(
                    $host,
                    $auth,
                    $remote,
                    $opts
                )
            );
        }

        if ($opts['files']) {
            // Restore the files backup.
            $filesBackupFile =  $this->backupFileName('.tar.gz', $remote['time']);
            $collection
                ->exec(
                    (string) CommandBuilder::create('rm')
                        ->addFlag('rf')
                        ->addArgument($local['filesdir'] . '/*')
                        ->addArgument($local['filesdir'] . '/.??*')
                )
                ->exec(
                    (string) CommandBuilder::create('tar')
                        ->addFlag('xkz')
                        ->addFlag('f', $filesBackupFile)
                        ->addFlag('C', $local['filesdir'])
                )
                ->exec(
                    (string) CommandBuilder::create('rm')
                        ->addFlag('f')
                        ->addArgument($filesBackupFile)
                );
        }

        if ($opts['data']) {
            // Restore the db backup.
            $dbBackupFile =  $this->backupFileName('.sql.gz', $remote['time']);
            $dbRestore = CommandBuilder::create('vendor/bin/robo digipolis:database-restore')->addOption('source', $dbBackupFile);
            $cwd = getcwd();

            $collection->taskExecStack();
            $collection->exec(
                (string) CommandBuilder::create('cd')
                    ->addArgument($this->getConfig()->get('digipolis.root.project'))
                    ->onSuccess($dbRestore)
            );
            $collection->exec(
                (string) CommandBuilder::create('cd')
                    ->addArgument($cwd)
                    ->onSuccess(
                        CommandBuilder::create('rm')
                            ->addFlag('rf')
                            ->addArgument($dbBackupFile)
                    )
            );
        }

        return $collection;
    }
}

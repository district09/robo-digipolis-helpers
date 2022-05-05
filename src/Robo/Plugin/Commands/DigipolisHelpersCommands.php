<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Consolidation\Config\ConfigAwareTrait;
use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\DependencyInjection\BackupTaskFactoryAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\RemoteHelperAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\ServiceProvider;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\BackupTaskFactoryAware;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\RemoteHelperAware;
use DigipolisGent\Robo\Helpers\Util\Path;
use DigipolisGent\Robo\Helpers\Util\RemoteHelper;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\AbstractApp;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Backup;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\BackupConfigTrait;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use League\Container\ContainerAwareInterface;
use League\Container\DefinitionContainerInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Symfony\ConsoleIO;
use Robo\Task\Filesystem\FilesystemStack;

abstract class DigipolisHelpersCommands extends \Robo\Tasks implements
    ConfigAwareInterface,
    RemoteHelperAwareInterface,
    BackupTaskFactoryAwareInterface,
    CustomEventAwareInterface
{
    use \DigipolisGent\Robo\Task\Deploy\Tasks;
    use ConfigAwareTrait;
    use \DigipolisGent\Robo\Helpers\Traits\Tasks;
    use BackupTaskFactoryAware;
    use RemoteHelperAware;
    use CustomEventAwareTrait;
    use BackupConfigTrait;

    public function setContainer(DefinitionContainerInterface $container): ContainerAwareInterface
    {
        parent::setContainer($container);

        $container->addShared(AbstractApp::class, [$this->getAppTaskFactoryClass(), 'create'])->addArgument($container);
        $container->addServiceProvider(new ServiceProvider());

        // Inject all our dependencies.
        $this->setRemoteHelper($container->get(RemoteHelper::class));
        $this->setBackupTaskFactory($container->get(Backup::class));

        return $this;
    }

    abstract public function getAppTaskFactoryClass();

    /**
     * @return FilesystemStack
     */
    protected function taskFilesystemStack()
    {
        return $this->task(FilesystemStack::class);
    }

    /**
     * Mirror a directory.
     *
     * @param string $dir
     *   Path of the directory to mirror.
     * @param string $destination
     *   Path of the directory where $dir should be mirrored.
     *
     * @return \Robo\Contract\TaskInterface
     *   The mirror dir task.
     *
     * @command digipolis:mirror-dir
     */
    public function digipolisMirrorDir(ConsoleIO $io, $dir, $destination)
    {
        if (!is_dir($dir)) {
            return;
        }
        $task = $this->taskFilesystemStack();
        $task->mkdir($destination);

        $directoryIterator = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($recursiveIterator as $item) {
            $destinationFile = $destination . '/' . $recursiveIterator->getSubPathName();
            if (file_exists($destinationFile)) {
                continue;
            }
            if (is_link($item)) {
                if ($item->getRealPath() !== false) {
                    $task->symlink($item->getLinkTarget(), $destinationFile);
                }
                continue;
            }
            if ($item->isDir()) {
                $task->mkdir($destinationFile);
                continue;
            }
            $task->copy($item, $destinationFile);
        }
        return $task;
    }

    /**
     * Polyfill for realpath.
     *
     * @param string $path
     *
     * @return string
     *
     * @command digipolis:realpath
     */
    public function digipolisRealpath($path)
    {
        return Path::realpath($path);
    }

    /**
     * Switch the current release symlink to the previous release.
     *
     * @param string $releasesDir
     *   Path to the folder containing all releases.
     * @param string $currentSymlink
     *   Path to the current release symlink.
     *
     * @command digipolis:switch-previous
     */
    public function digipolisSwitchPrevious($releasesDir, $currentSymlink)
    {
        return $this->taskSwitchPrevious($releasesDir, $currentSymlink);
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

        $remote = $this->remoteHelper->getRemoteSettings($host, $user, $keyFile, $opts['app']);
        $local = $this->remoteHelper->getLocalSettings($opts['app']);
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

                $backupConfig = $this->getBackupConfig();
                $dirs = ($backupConfig['file_backup_subdirs'] ? $backupConfig['file_backup_subdirs'] : ['']);

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

                    $backupConfig = $this->getBackupConfig();
                    foreach ($backupConfig['exclude_from_backup'] as $exclude) {
                        $rsync->exclude($exclude);
                    }

                    $collection->addTask($rsync);
                }
            }
        }

        if ($opts['data'] || $opts['files']) {
            // Create a backup.
            $collection->addTask(
                $this->backupTaskFactory->backupTask(
                    $host,
                    $auth,
                    $remote,
                    $opts
                )
            );
            // Download the backup.
            $collection->addTask(
                $this->backupTaskFactory->downloadBackupTask(
                    $host,
                    $auth,
                    $remote,
                    $opts
                )
            );
        }

        if ($opts['files']) {
            // Restore the files backup.
            $filesBackupFile =  $this->backupTaskFactory->backupFileName('.tar.gz', $remote['time']);
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
            $dbBackupFile =  $this->backupTaskFactory->backupFileName('.sql.gz', $remote['time']);
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

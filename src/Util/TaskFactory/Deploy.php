<?php

namespace DigipolisGent\Robo\Helpers\Util\TaskFactory;

use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\DependencyInjection\AppTaskFactoryAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\BackupTaskFactoryAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\BuildTaskFactoryAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\CacheTaskFactoryAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\PropertiesHelperAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\RemoteHelperAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\AppTaskFactoryAware;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\BackupTaskFactoryAware;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\BuildTaskFactoryAware;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\CacheTaskFactoryAware;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\PropertiesHelperAware;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\RemoteHelperAware;
use DigipolisGent\Robo\Helpers\Util\PropertiesHelper;
use DigipolisGent\Robo\Helpers\Util\RemoteHelper;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Symfony\Component\Console\Input\InputAwareInterface;

class Deploy implements
    AppTaskFactoryAwareInterface,
    BackupTaskFactoryAwareInterface,
    BuilderAwareInterface,
    BuildTaskFactoryAwareInterface,
    CacheTaskFactoryAwareInterface,
    PropertiesHelperAwareInterface,
    RemoteHelperAwareInterface,
    InputAwareInterface,
    OutputAwareInterface
{
    use TaskAccessor;
    use \DigipolisGent\Robo\Helpers\Traits\Tasks;
    use \DigipolisGent\Robo\Task\Deploy\Tasks;
    use \Robo\Task\Base\Tasks;
    use RemoteHelperAware;
    use BuildTaskFactoryAware;
    use BackupTaskFactoryAware;
    use CacheTaskFactoryAware;
    use PropertiesHelperAware;
    use AppTaskFactoryAware;
    use IO;

    public function __construct(
        AbstractApp $appTaskFactory,
        Backup $backupTaskFactory,
        Build $buildTaskFactory,
        Cache $cacheTaskFactory,
        PropertiesHelper $propertiesHelper,
        RemoteHelper $remoteHelper
    ) {
        $this->setAppTaskFactory($appTaskFactory);
        $this->setBackupTaskFactory($backupTaskFactory);
        $this->setBuildTaskFactory($buildTaskFactory);
        $this->setCacheTaskFactory($cacheTaskFactory);
        $this->setPropertiesHelper($propertiesHelper);
        $this->setRemoteHelper($remoteHelper);
    }

    public static function create(DefinitionContainerInterface $container)
    {
        $object = new static(
            $container->get(AbstractApp::class),
            $container->get(Backup::class),
            $container->get(Build::class),
            $container->get(Cache::class),
            $container->get(PropertiesHelper::class),
            $container->get(RemoteHelper::class)
        );
        $object->setBuilder(CollectionBuilder::create($container, $object));

        return $object;
    }

    /**
     * Build a site and push it to the servers.
     *
     * @param array $arguments
     *   Variable amount of arguments. The last argument is the path to the
     *   the private key file (ssh), the penultimate is the ssh user. All
     *   arguments before that are server IP's to deploy to.
     * @param array $opts
     *   The options for this task.
     *
     * @return \Robo\Contract\TaskInterface
     *   The deploy task.
     */
    public function deployTask(
        array $arguments,
        $opts
    ) {
        // Define variables.
        $opts += ['force-install' => false];
        $privateKeyFile = array_pop($arguments);
        $user = array_pop($arguments);
        $servers = $arguments;
        $worker = is_null($opts['worker']) ? reset($servers) : $opts['worker'];
        $remote = $this->remoteHelper->getRemoteSettings($servers, $user, $privateKeyFile, $opts['app']);
        $releaseDir = $remote['releasesdir'] . '/' . $remote['time'];
        $auth = new KeyFile($user, $privateKeyFile);
        $archive = $remote['time'] . '.tar.gz';
        $backupOpts = ['files' => false, 'data' => true];

        $collection = $this->collectionBuilder();

        // Build the archive to deploy.
        $collection->addTask($this->buildTaskFactory->buildTask($archive));

        // Create a backup and a rollback task if a site is already installed.
        if ($remote['createbackup'] && $this->appTaskFactory->isSiteInstalled($worker, $auth, $remote) && $this->remoteHelper->currentReleaseHasRobo($worker, $auth, $remote)) {
            // Create a backup.
            $collection->addTask($this->backupTaskFactory->backupTask($worker, $auth, $remote, $backupOpts));

            // Create a rollback for this backup for when the deploy fails.
            $collection->rollback(
                $this->backupTaskFactory->restoreBackupTask(
                    $worker,
                    $auth,
                    $remote,
                    $backupOpts
                )
            );
        }

        // Push the package to the servers and create the required symlinks.
        foreach ($servers as $server) {
            // Remove this release on rollback.
            $collection->rollback($this->removeFailedReleaseTask($server, $auth, $remote, $releaseDir));

            // Clear opcache (if present) on rollback.
            if (isset($remote['opcache']) && (!array_key_exists('clear', $remote['opcache']) || $remote['opcache']['clear'])) {
                $collection->rollback($this->cacheTaskFactory->clearOpCacheTask($server, $auth, $remote));
            }

            // Push the package.
            $collection->addTask($this->pushPackageTask($server, $auth, $remote, $archive));

            // Add any tasks to execute before creating the symlinks.
            $preSymlink = $this->preSymlinkTask($server, $auth, $remote);
            if ($preSymlink) {
                $collection->addTask($preSymlink);
            }

            // Switch the current symlink to the previous release on rollback.
            $collection->rollback($this->switchPreviousTask($server, $auth, $remote));

            // Create the symlinks.
            $collection->addTask($this->symlinksTask($server, $auth, $remote));
            $postSymlink = $this->postSymlinkTask($server, $auth, $remote);
            if ($postSymlink) {
                $collection->addTask($postSymlink);
            }
        }

        // Initialize the site (update or install).
        $collection->addTask($this->initRemoteTask($worker, $auth, $remote, $opts, $opts['force-install']));

        // Clear cache after update or install.
        $clearCache = $this->cacheTaskFactory->clearCacheTask($worker, $auth, $remote);
        if ($clearCache) {
            $collection->addTask($clearCache);
        }

        // Clear OPcache if present.
        if (isset($remote['opcache']) && (!array_key_exists('clear', $remote['opcache']) || $remote['opcache']['clear'])) {
            foreach ($servers as $server) {
                $collection->addTask($this->cacheTaskFactory->clearOpCacheTask($server, $auth, $remote));
            }
        }

        foreach ($servers as $server) {
            // Compress old releases if configured.
            if (isset($remote['compress_old_releases']) && $remote['compress_old_releases']) {
                // The current release (the one we're replacing).
                $currentRelease = $this->remoteHelper->getCurrentProjectRoot($server, $auth, $remote);
                // Strip the releases dir from the current release, so the tar
                // contains relative paths.
                $relativeCurrentRelease = str_replace($remote['releasesdir'] . '/', '', $currentRelease);
                $collection->addTask(
                    $this->taskSsh($server, $auth)
                        ->remoteDirectory($remote['releasesdir'])
                        ->exec((string) CommandBuilder::create('tar')
                          ->addFlag('c')
                          ->addFlag('z')
                          ->addFlag('f', $relativeCurrentRelease . '.tar.gz')
                          ->addArgument($relativeCurrentRelease)
                          ->onSuccess(
                              CommandBuilder::create('rm')
                                  ->addFlag('r')
                                  ->addFlag('f')
                                  ->addArgument($relativeCurrentRelease)
                          )
                          ->onFailure(
                              CommandBuilder::create('rm')
                                  ->addFlag('r')
                                  ->addFlag('f')
                                  ->addArgument($relativeCurrentRelease . '.tar.gz')
                          )
                    )
                );
            }
            // Clean release and backup dirs on the servers.
            $collection->completion($this->cleanDirsTask($server, $auth, $remote));
        }

        // Clear the site's cache on rollback too.
        if ($clearCache) {
            $collection->completion($clearCache);
        }

        return $collection;
    }

    /**
     * Tasks to execute before creating the symlinks.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return bool|\Robo\Contract\TaskInterface
     *   The presymlink task, false if no pre symlink tasks need to run.
     */
    protected function preSymlinkTask($worker, AbstractAuth $auth, $remote)
    {
        $collection = $this->collectionBuilder();
        foreach ($remote['symlinks'] as $symlink) {
            $preIndividualSymlinkTask = $this->preIndividualSymlinkTask($worker, $auth, $remote, $symlink);
            if ($preIndividualSymlinkTask) {
                $collection->addTask($preIndividualSymlinkTask);
            }
        }
        return $collection;
    }

     /**
     * Tasks to execute before creating an individual symlink.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     * @param string $symlink
      *  The symlink in format "target:link".
     *
     * @return bool|\Robo\Contract\TaskInterface
     *   The presymlink task, false if no pre symlink task needs to run.
     */
    protected function preIndividualSymlinkTask($worker, AbstractAuth $auth, $remote, $symlink)
    {
        $projectRoot = $remote['rootdir'];
        $collection = $this->collectionBuilder();
        $collection->taskSsh($worker, $auth)
            ->remoteDirectory($projectRoot, true)
            ->timeout($this->remoteHelper->getTimeoutSetting('presymlink_mirror_dir'));
        list($target, $link) = explode(':', $symlink);
        if ($link === $remote['currentdir']) {
            return;
        }
        // If the link we're going to create is an existing directory,
        // mirror that directory on the symlink target and then delete it
        // before creating the symlink
        $collection->exec(
            (string) CommandBuilder::create('vendor/bin/robo digipolis:mirror-dir')
                ->addArgument($link)
                ->addArgument($target)
        );
        $collection->exec(
            (string) CommandBuilder::create('rm')
                ->addFlag('rf')
                ->addArgument($link)
        );

        return $collection;
    }



    /**
     * Create all required symlinks on the server.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The symlink task.
     */
    protected function symlinksTask($worker, AbstractAuth $auth, $remote)
    {
        $collection = $this->collectionBuilder();
        foreach ($remote['symlinks'] as $link) {
            $preIndividualSymlinkTask = $this->preIndividualSymlinkTask($worker, $auth, $remote, $link);
            if ($preIndividualSymlinkTask) {
                $collection->addTask($preIndividualSymlinkTask);
            }
            list($target, $linkname) = explode(':', $link);
            $collection->taskSsh($worker, $auth)
                ->exec(
                    (string) CommandBuilder::create('ln')
                        ->addFlag('s')
                        ->addFlag('T')
                        ->addFlag('f')
                        ->addArgument($target)
                        ->addArgument($linkname)
                );
        }
        return $collection;
    }

    /**
     * Tasks to execute after creating the symlinks.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return bool|\Robo\Contract\TaskInterface
     *   The postsymlink task, false if no post symlink tasks need to run.
     */
    protected function postSymlinkTask($worker, AbstractAuth $auth, $remote)
    {
        if (isset($remote['postsymlink_filechecks']) && $remote['postsymlink_filechecks']) {
            $projectRoot = $remote['rootdir'];
            $collection = $this->collectionBuilder();
            $collection->taskSsh($worker, $auth)
                ->remoteDirectory($projectRoot, true)
                ->timeout($this->remoteHelper->getTimeoutSetting('postsymlink_filechecks'));
            foreach ($remote['postsymlink_filechecks'] as $file) {
                // If this command fails, the collection will fail, which will
                // trigger a rollback.
                $builder = CommandBuilder::create('ls')
                    ->addArgument($file)
                    ->pipeOutputTo('grep')
                    ->addArgument($file)
                    ->onFailure(
                        CommandBuilder::create('echo')
                            ->addArgument('[ERROR] ' . $file . ' was not found.')
                            ->onFinished('exit')
                            ->addArgument('1')
                    );
                $collection->exec((string) $builder);
            }
            return $collection;
        }
        return false;
    }

    /**
     * Install or update a remote site.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     * @param array $extra
     *   Extra parameters to pass to site install.
     * @param bool $force
     *   Whether or not to force the install even when the site is present.
     *
     * @return \Robo\Contract\TaskInterface
     *   The init remote task.
     */
    protected function initRemoteTask($worker, AbstractAuth $auth, $remote, $extra = [], $force = false)
    {
        $collection = $this->collectionBuilder();
        if (!$this->appTaskFactory->isSiteInstalled($worker, $auth, $remote) || $force) {
            $this->say($force ? 'Forcing site install.' : 'Site status failed.');
            $this->say('Triggering install script.');

            $collection->addTask($this->appTaskFactory->installTask($worker, $auth, $remote, $extra, $force));
            return $collection;
        }
        $collection->addTask($this->appTaskFactory->updateTask($worker, $auth, $remote, $extra));
        return $collection;
    }

    /**
     * Build a site and package it.
     *
     * @param string $archivename
     *   Name of the archive to create.
     *
     * @return \Robo\Contract\TaskInterface
     *   The deploy task.
     */
    protected function buildTask($archivename = null)
    {
        $this->propertiesHelper->readProperties();
        $archive = is_null($archivename) ? $this->time . '.tar.gz' : $archivename;
        $collection = $this->collectionBuilder();
        $collection
            ->taskPackageProject($archive);
        return $collection;
    }

    /**
     * Remove a failed release from the server.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     * @param string|null $releaseDirname
     *   The path of the release dir to remove.
     *
     * @return \Robo\Contract\TaskInterface
     *   The remove release task.
     */
    protected function removeFailedReleaseTask($worker, AbstractAuth $auth, $remote, $releaseDirname = null)
    {
        $releaseDir = is_null($releaseDirname)
            ? $remote['releasesdir'] . '/' . $remote['time']
            : $releaseDirname;
        return $this->taskRemoteRemoveRelease($worker, $auth, null, $releaseDir);
    }



    /**
     * Push a package to the server.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     * @param string|null $archivename
     *   The path to the package to push.
     *
     * @return \Robo\Contract\TaskInterface
     *   The push package task.
     */
    protected function pushPackageTask($worker, AbstractAuth $auth, $remote, $archivename = null)
    {
        $archive = is_null($archivename)
            ? $remote['time'] . '.tar.gz'
            : $archivename;
        $releaseDir = $remote['releasesdir'] . '/' . $remote['time'];
        $collection = $this->collectionBuilder();
        $collection->taskPushPackage($worker, $auth)
            ->destinationFolder($releaseDir)
            ->package($archive);

        $collection->taskSsh($worker, $auth)
            ->remoteDirectory($releaseDir, true)
            ->exec((string) CommandBuilder::create('chmod')
                ->addArgument('u+rx')
                ->addArgument('vendor/bin/robo')
            );

        return $collection;
    }

    /**
     * Switch the current symlink to the previous release on the server.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The switch previous task.
     */
    protected function switchPreviousTask($worker, AbstractAuth $auth, $remote)
    {
        return $this->taskRemoteSwitchPrevious(
            $worker,
            $auth,
            $this->remoteHelper->getCurrentProjectRoot($worker, $auth, $remote),
            $remote['releasesdir'],
            $remote['currentdir']
        );
    }

    /**
     * Clean the release and backup directories on the server.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The clean directories task.
     */
    protected function cleanDirsTask($worker, AbstractAuth $auth, $remote)
    {
        $cleandirLimit = isset($remote['cleandir_limit']) ? max(1, $remote['cleandir_limit']) : '';
        $collection = $this->collectionBuilder();
        $collection->taskRemoteCleanDirs($worker, $auth, $remote['rootdir'], $remote['releasesdir'], ($cleandirLimit ? ($cleandirLimit + 1) : false));

        if ($remote['createbackup']) {
            $collection->taskRemoteCleanDirs($worker, $auth, $remote['rootdir'], $remote['backupsdir'], ($cleandirLimit ? ($cleandirLimit) : false));
        }

        return $collection;
    }

}

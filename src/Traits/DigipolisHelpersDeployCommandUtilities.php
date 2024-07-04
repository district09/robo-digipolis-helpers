<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;

trait DigipolisHelpersDeployCommandUtilities
{
    use CustomEventAwareTrait;
    use EventDispatcher;
    use DigipolisHelpersCommandUtilities;
    use CommandWithBackups;

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
     *
     * @option force-install
     *   Force install even if we could just update.
     * @option worker
     *   For load-balanced environments, server to execute the updates on.
     * @option app
     *   The name of the app
     *
     * @command digipolis:deploy
     */
    protected function deploy(
        array $arguments,
        $opts = [
            'force-install' => false,
            'worker' => null,
            'app' => 'default',
        ]
    ) {
        // Define variables.
        $opts += ['force-install' => false];
        $privateKeyFile = array_pop($arguments);
        $user = array_pop($arguments);
        $servers = $arguments;
        $worker = is_null($opts['worker']) ? reset($servers) : $opts['worker'];
        $remoteSettings = $this->getRemoteSettings($servers, $user, $privateKeyFile, $opts['app']);
        $workerCurrentProjectRoot = $this->getCurrentProjectRoot($worker, $user, $privateKeyFile, $remoteSettings);
        $releaseDir = $remoteSettings['releasesdir'] . '/' . $remoteSettings['time'];
        $archive = $remoteSettings['time'] . '.tar.gz';
        $backupOpts = ['files' => false, 'data' => true];
        $workerRemoteConfig = new RemoteConfig($worker, $user, $privateKeyFile, $remoteSettings, $workerCurrentProjectRoot);

        $collection = $this->collectionBuilder();

        // Build the archive to deploy.
        $collection->addTask($this->buildTask($archive));

        // Create a backup and a rollback task if a site is already installed.
        if (
            $remoteSettings['createbackup']
            && $this->isSiteInstalled($workerRemoteConfig)
            && $this->currentReleaseHasRobo($workerRemoteConfig)
        ) {
            // Create a backup.
            $collection->addTask($this->backupRemoteTask($workerRemoteConfig, $backupOpts));

            // Create a rollback for this backup for when the deploy fails.
            // Rollback tasks are executed in reverse order.
            $collection->rollback($this->restoreBackupRemoteTask($workerRemoteConfig, $backupOpts));
            $collection->rollback($this->preRestoreBackupRemoteTask($workerRemoteConfig, $backupOpts));
        }

        // Push the package to the servers and create the required symlinks.
        foreach ($servers as $server) {
            $serverProjectRoot = $this->getCurrentProjectRoot($server, $user, $privateKeyFile, $remoteSettings);
            $serverRemoteConfig = new RemoteConfig($server, $user, $privateKeyFile, $remoteSettings, $serverProjectRoot);
            // Remove this release on rollback.
            $collection->rollback($this->removeFailedReleaseTask($serverRemoteConfig, $releaseDir));

            // Clear opcache (if present) on rollback.
            if (isset($remoteSettings['opcache']) && (!array_key_exists('clear', $remoteSettings['opcache']) || $remoteSettings['opcache']['clear'])) {
                $collection->rollback($this->clearRemoteOpcacheTask($serverRemoteConfig));
            }

            // Push the package.
            $collection->addTask($this->pushPackageTask($serverRemoteConfig, $archive));

            // Add any tasks to execute before creating the symlinks.
            $collection->addTask($this->preSymlinkTask($serverRemoteConfig));

            // Switch the current symlink to the previous release on rollback.
            $collection->rollback($this->remoteSwitchPreviousTask($serverRemoteConfig));

            // Create the symlinks.
            $collection->addTask($this->remoteSymlinksTask($serverRemoteConfig));

            // Add any tasks to execute after creating the symlinks.
            $collection->addTask($this->postSymlinkTask($serverRemoteConfig));
        }

        // Initialize the site (update or install).
        $collection->addTask($this->initRemoteTask($workerRemoteConfig, $opts, $opts['force-install']));

        // Clear cache after update or install.
        $collection->addTask($this->clearCacheTask($workerRemoteConfig));

        foreach ($servers as $server) {
            $serverProjectRoot = $this->getCurrentProjectRoot($server, $user, $privateKeyFile, $remoteSettings);
            $serverRemoteConfig = new RemoteConfig($server, $user, $privateKeyFile, $remoteSettings, $serverProjectRoot);
            // Clear OPcache if present.
            if (isset($remoteSettings['opcache']) && (!array_key_exists('clear', $remoteSettings['opcache']) || $remoteSettings['opcache']['clear'])) {
                $collection->addTask($this->clearRemoteOpcacheTask($serverRemoteConfig));
            }
            // Compress old releases if configured.
            if (isset($remoteSettings['compress_old_releases']) && $remoteSettings['compress_old_releases']) {
                $collection->addTask($this->compressOldReleaseTask($serverRemoteConfig));
            }
            // Clean release and backup dirs on the servers.
            $collection->completion($this->cleanDirsTask($serverRemoteConfig));
        }

        // Clear the site's cache on rollback too.
        $collection->completion($this->clearCacheTask($workerRemoteConfig));

        return $collection;
    }

    /**
     * Get the task that will create a release archive.
     *
     * @param string $archiveName
     *   The name of the archive that will be created.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function buildTask($archiveName)
    {
        return $this->handleTaskEvent('digipolis:build-task', ['archiveName' => $archiveName]);
    }

    /**
     * Get the task that will remove a failed release from the host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param string $releaseDir
     *   The release directory to remove.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function removeFailedReleaseTask(RemoteConfig $remoteConfig, $releaseDir)
    {
        return $this->handleTaskEvent(
            'digipolis:remove-failed-release',
            [
                'remoteConfig' => $remoteConfig,
                'releaseDir' => $releaseDir,
            ]
        );
    }

    /**
     * Get the task that will clear opcache on a host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function clearRemoteOpcacheTask(RemoteConfig $remoteConfig)
    {
        return $this->handleTaskEvent(
            'digipolis:clear-remote-opcache',
            [
                'remoteConfig' => $remoteConfig,
                'timeouts' => [
                    'clear_op_cache' => $this->getTimeoutSetting('clear_op_cache'),
                ],
            ]
        );
    }

    /**
     * Get the task that will push a release archive to a host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param string $archiveName
     *   The path to the archive to push.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function pushPackageTask(RemoteConfig $remoteConfig, $archiveName)
    {
        return $this->handleTaskEvent(
            'digipolis:push-package',
            [
                'remoteConfig' => $remoteConfig,
                'archiveName' => $archiveName,
            ]
        );
    }

    /**
     * Get the task that will execute presymlink tasks.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function preSymlinkTask(RemoteConfig $remoteConfig)
    {
        return $this->handleTaskEvent(
            'digipolis:pre-symlink',
            [
                'remoteConfig' => $remoteConfig,
                'timeouts' => [
                    'pre_symlink' => $this->getTimeoutSetting('pre_symlink'),
                ],
            ]
        );
    }

    /**
     * Get the task that will switch to the previous release on the host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function remoteSwitchPreviousTask(RemoteConfig $remoteConfig)
    {
        return $this->handleTaskEvent(
            'digipolis:remote-switch-previous',
            [
                'remoteConfig' => $remoteConfig,
            ]
        );
    }

    /**
     * Get the task that will create the configured symlinks on the host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return string
     */
    protected function remoteSymlinksTask(RemoteConfig $remoteConfig)
    {
        return $this->handleTaskEvent(
            'digipolis:remote-symlink',
            [
                'remoteConfig' => $remoteConfig,
                'timeouts' => [
                    'symlink' => $this->getTimeoutSetting('symlink'),
                ],
            ]
        );
    }

    /**
     * Get the task that will execute postsymlink tasks on the host
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function postSymlinkTask(RemoteConfig $remoteConfig)
    {
        return $this->handleTaskEvent(
            'digipolis:post-symlink',
            [
                'remoteConfig' => $remoteConfig,
                'timeouts' => [
                    'post_symlink' => $this->getTimeoutSetting('post_symlink'),
                ],
            ]
        );
    }

    /**
     * Get the task that will install or update a site on the host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param array $options
     *   Extra parameters to pass to site install.
     * @param bool $force
     *   Whether or not to force the install even when the site is present.
     *
     * @return \Robo\Contract\TaskInterface
     *   The init remote task.
     */
    protected function initRemoteTask(RemoteConfig $remoteConfig, $options = [], $force = false)
    {
        $collection = $this->collectionBuilder();
        if (!$this->isSiteInstalled($remoteConfig) || $force) {
            $this->say($force ? 'Forcing site install.' : 'Site status failed.');
            $this->say('Triggering install script.');

            $collection->addTask($this->handleTaskEvent(
                'digipolis:install',
                [
                    'remoteConfig' => $remoteConfig,
                    'options'=> $options,
                    'force' => $force,
                ]
            ));

            return $collection;
        }
        $collection->addTask($this->handleTaskEvent(
            'digipolis:update',
            [
                'remoteConfig' => $remoteConfig,
                'options'=> $options,
                'force' => $force,
            ]
        ));

        return $collection;
    }

    /**
     * Get the task that will compress an old release on the host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return type
     */
    protected function compressOldReleaseTask(RemoteConfig $remoteConfig)
    {
        return $this->handleTaskEvent(
            'digipolis:compress-old-release',
            [
                'remoteConfig' => $remoteConfig,
                'releaseToCompress' => $remoteConfig->getCurrentProjectRoot(),
                'timeouts' => [
                    'compress_old_release' => $this->getTimeoutSetting('compress_old_release'),
                ],
            ]
        );
    }

    /**
     * Get the task that will clean the directories (remove old releases).
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function cleanDirsTask(RemoteConfig $remoteConfig)
    {
        return $this->handleTaskEvent(
            'digipolis:clean-dirs',
            [
                'remoteConfig' => $remoteConfig,
            ]
        );
    }
}

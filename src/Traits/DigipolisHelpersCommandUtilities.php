<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use Ckr\Util\ArrayMerger;
use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Helpers\Util\TimeHelper;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;

trait DigipolisHelpersCommandUtilities
{
    protected $remoteSettingsCache = [];
    protected $localSettingsCache = [];

    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    /**
     * Get the settings from the 'remote' config key, with the tokens replaced.
     *
     * @param string|array $servers
     *   The IP address of the server to get the settings for.
     * @param string $user
     *   The SSH user used to connect to the server.
     * @param string $privateKeyFile
     *   The path to the private key file used to connect to the server.
     * @param string $app
     *   The name of the app these settings apply to.
     * @param string|null $timestamp
     *   The timestamp to use. Defaults to the request time.
     *
     * @return array
     *   The settings for this server and app.
     */
    protected function getRemoteSettings($servers, $user, $privateKeyFile, $app, $timestamp = null)
    {
        $timestamp = is_null($timestamp) ? TimeHelper::getInstance()->getTime() : $timestamp;
        $servers = (array) $servers;
        $serversCopy = $servers;
        sort($serversCopy);
        $serversKey = implode('_', $serversCopy);
        $cacheKeyParts = [$serversKey, $user, $privateKeyFile, $app, $timestamp];
        $cacheKey = implode(':', $cacheKeyParts);
        if (!isset($this->remoteSettingsCache[$cacheKey])) {
            $results = $this->handleEvent(
                'digipolis:get-remote-settings',
                [
                    'servers' => $servers,
                    'user' => $user,
                    'privateKeyFile' => $privateKeyFile,
                    'app' => $app,
                    'timestamp' => $timestamp,
                ]
            );
            $settings = array_shift($results);
            while ($results) {
                $settings = ArrayMerger::doMerge($settings, array_shift($results));
            }
            $this->remoteSettingsCache[$cacheKey] = $settings;
        }

        return $this->remoteSettingsCache[$cacheKey];
    }

    /**
     * Get the settings from the 'local' config key, with the tokens replaced.
     *
     * @param string $app
     *   The name of the app these settings apply to.
     * @param string|null $timestamp
     *   The timestamp to use. Defaults to the request time.
     *
     * @return array
     *   The settings for the local environment and app.
     */
    protected function getLocalSettings($app, $timestamp = null)
    {
        $timestamp = is_null($timestamp) ? TimeHelper::getInstance()->getTime() : $timestamp;
        $cacheKey = $app . ':' . $timestamp;
        if (!isset($this->localSettingsCache[$cacheKey])) {
            $results = $this->handleEvent(
                'digipolis:get-local-settings',
                [
                    'app' => $app,
                    'timestamp' => $timestamp,
                ]
            );
            $settings = array_shift($results);
            while ($results) {
                $settings = ArrayMerger::doMerge($settings, array_shift($results));
            }
            $this->localSettingsCache[$cacheKey] = $settings;
        }

        return $this->localSettingsCache[$cacheKey];
    }

    /**
     * Gets the config for file backups.
     *
     * @return array
     *   The backup config with keys file_backup_subdirs and exclude_from_backup
     */
    protected function getFileBackupConfig()
    {
        $configs = $this->handleEvent('digipolis:file-backup-config', []);
        $config = [
            'file_backup_subdirs' => [],
            'exclude_from_backup' => [],
        ];
        while ($configs) {
            $config = ArrayMerger::doMerge($config, array_shift($configs));
        }

        return $config;
    }

    /**
     * Get an ssh timeout setting.
     *
     * @param string $type
     *   The type to get the setting for.
     *
     * @return int
     *   The timeout in seconds.
     */
    protected function getTimeoutSetting($type)
    {
        $settings = $this->handleEvent('digipolis:timeout-setting', ['type' => $type]);
        return max($settings);
    }

    /**
     * Get the project root of the current release on the host.
     *
     * @param string $host
     *   The host ip.
     * @param string $user
     *   The ssh user.
     * @param string $privateKeyFile
     *   The path to the private ssh key.
     * @param array $remoteSettings
     *   The remote settings as returned by static::getRemoteSettings().
     *
     * @return string
     *   The path to the project root on the server.
     */
    protected function getCurrentProjectRoot($host, $user, $privateKeyFile, $remoteSettings)
    {
        $results = $this->handleEvent(
            'digipolis:current-project-root',
            [
                'host' => $host,
                'user' => $user,
                'privateKeyFile' => $privateKeyFile,
                'remoteSettings' => $remoteSettings,
            ]
        );

        return reset($results);
    }

    /**
     * Check if a site is already installed
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return bool
     *   Whether or not the site is installed.
     */
    protected function isSiteInstalled(RemoteConfig $remoteConfig)
    {
        $results = $this->handleEvent(
            'digipolis:is-site-installed',
            [
                'remoteConfig' => $remoteConfig,
            ]
        );

        return reset($results);
    }

    /**
     * Check if the current release has robo available.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return bool
     */
    protected function currentReleaseHasRobo(RemoteConfig $remoteConfig)
    {
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        return $this->taskSsh($remoteConfig->getHost(), $auth)
            ->remoteDirectory($remoteConfig->getCurrentProjectRoot(), true)
            ->exec(
                (string) CommandBuilder::create('ls')
                    ->addArgument('vendor/bin/robo')
                    ->pipeOutputTo(
                        CommandBuilder::create('grep')
                            ->addArgument('robo')
                    )
            )
            ->run()
            ->wasSuccessful();
    }

    /**
     * Get the task that will clear the cache on the host.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function clearCacheTask(RemoteConfig $remoteConfig)
    {
        return $this->handleTaskEvent(
            'digipolis:clear-cache',
            [
                'remoteConfig' => $remoteConfig,
            ]
        );
    }
}

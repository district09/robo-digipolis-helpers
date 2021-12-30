<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use Ckr\Util\ArrayMerger;
use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Robo\Task\Remote\Rsync;

trait AbstractCommandTrait
{
    use \DigipolisGent\Robo\Task\General\Common\DigipolisPropertiesAware;
    use \Consolidation\Config\ConfigAwareTrait;
    use \DigipolisGent\Robo\Task\General\loadTasks;
    use \DigipolisGent\Robo\Task\Deploy\Traits\SshTrait;
    use \DigipolisGent\Robo\Task\Deploy\Traits\SFTPTrait;
    use \DigipolisGent\Robo\Helpers\Traits\loadTasks;
    use \Robo\Task\Base\loadTasks;
    use TraitDependencyCheckerTrait;

    public static $defaultEnvironmentOverrideSettings = [
        'environment_env_var' => 'HOSTNAME',
        'environment_matcher' => '\\DigipolisGent\\Robo\\Helpers\\Util\\EnvironmentMatcher::regexMatch',
    ];

    /**
     * Stores the request time.
     *
     * @var int
     */
    protected $time;

    /**
     * Create a RoboFileBase instance.
     */
    public function __construct()
    {
        $this->time = time();
        $this->checkTraitDependencies();
    }

    /**
     * @return Rsync
     */
    protected function taskRsync()
    {
        return $this->task(Rsync::class);
    }

    /**
     * Get the settings from the 'remote' config key, with the tokens replaced.
     *
     * @param string $host
     *   The IP address of the server to get the settings for.
     * @param string $user
     *   The SSH user used to connect to the server.
     * @param string $keyFile
     *   The path to the private key file used to connect to the server.
     * @param string $app
     *   The name of the app these settings apply to.
     * @param string|null $timestamp
     *   The timestamp to use. Defaults to the request time.
     *
     * @return array
     *   The settings for this server and app.
     */
    protected function getRemoteSettings($host, $user, $keyFile, $app, $timestamp = null)
    {
        $this->readProperties();
        $defaults = [
            'user' => $user,
            'private-key' => $keyFile,
            'app' => $app,
            'createbackup' => true,
            'time' => is_null($timestamp) ? $this->time : $timestamp,
        ];

        // Set up destination config.
        $replacements = array(
            '[user]' => $user,
            '[private-key]' => $keyFile,
            '[app]' => $app,
            '[time]' => is_null($timestamp) ? $this->time : $timestamp,
        );
        if (is_string($host)) {
            $replacements['[server]'] = $host;
            $defaults['server'] = $host;
        }
        if (is_array($host)) {
            foreach ($host as $key => $server) {
                $replacements['[server-' . $key . ']'] = $server;
                $defaults['server-' . $key] = $server;
            }
        }

        $settings = $this->processEnvironmentOverrides(
            $this->tokenReplace($this->getConfig()->get('remote'), $replacements) + $defaults
        );

        // Reverse the symlinks so the `current` symlink is the last one to be
        // created.
        $settings['symlinks'] = array_reverse($settings['symlinks'], true);

        return $settings;
    }

    /**
     * Process environment-specific overrides.
     *
     * @param array $settings
     * @return array
     *
     * @see self::getRemoteSettings
     */
    protected function processEnvironmentOverrides($settings)
    {
        $settings += static::$defaultEnvironmentOverrideSettings;
        if (!isset($settings['environment_overrides']) || !$settings['environment_overrides']) {
            return $settings;
        }

        $server = $this->getFirstServer($settings);
        if (!$server) {
            return $settings;
        }

        // Parse the env var on the server.
        $auth = new KeyFile($settings['user'], $settings['private-key']);
        $fullOutput = '';
        $this->taskSsh($server, $auth)
            ->exec(
                (string) CommandBuilder::create('echo')
                    ->addRawArgument('$' . $settings['environment_env_var']),
                function ($output) use (&$fullOutput) {
                    $fullOutput .= $output;
                }
            )
            ->run();
        $envVarValue =  substr($fullOutput, 0, (strpos($fullOutput, "\n") ?: strlen($fullOutput)));
        foreach ($settings['environment_overrides'] as $environmentMatch => $overrides) {
            if (call_user_func($settings['environment_matcher'], $environmentMatch, $envVarValue)) {
                $settings = ArrayMerger::doMerge($settings, $overrides);
            }
        }
        return $settings;
    }

    /**
     * Get the first server entry from the remote settings.
     *
     * @param array $settings
     *
     * @return string|bool
     *   First server if found, false otherwise.
     *
     * @see self::processEnvironmentOverrides
     */
    protected function getFirstServer($settings)
    {
        foreach ($settings as $key => $value) {
            if (preg_match('/^server/', $key) === 1) {
                return $value;
            }
        }
        return false;
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
    protected function getLocalSettings($app = null, $timestamp = null)
    {
        $this->readProperties();
        $defaults = [
            'app' => $app,
            'time' => is_null($timestamp) ? $this->time : $timestamp,
            'project_root' => $this->getConfig()->get('digipolis.root.project'),
            'web_root' => $this->getConfig()->get('digipolis.root.web'),
        ];

        // Set up destination config.
        $replacements = array(
            '[project_root]' => $this->getConfig()->get('digipolis.root.project'),
            '[web_root]' => $this->getConfig()->get('digipolis.root.web'),
            '[app]' => $app,
            '[time]' => is_null($timestamp) ? $this->time : $timestamp,
        );
        return $this->tokenReplace($this->getConfig()->get('local'), $replacements) + $defaults;
    }

    /**
     * Helper functions to replace tokens in an array.
     *
     * @param string|array $input
     *   The array or string containing the tokens to replace.
     * @param array $replacements
     *   The token replacements.
     *
     * @return string|array
     *   The input with the tokens replaced with their values.
     */
    protected function tokenReplace($input, $replacements)
    {
        if (is_string($input)) {
            return strtr($input, $replacements);
        }
        if (is_scalar($input) || empty($input)) {
            return $input;
        }
        foreach ($input as &$i) {
            $i = $this->tokenReplace($i, $replacements);
        }
        return $input;
    }

    protected function getCurrentProjectRoot($worker, AbstractAuth $auth, $remote)
    {
        $fullOutput = '';
        $this->taskSsh($worker, $auth)
            ->remoteDirectory($remote['releasesdir'], true)
            ->exec(
                (string) CommandBuilder::create('ls')
                    ->addFlag('1')
                    ->pipeOutputTo(
                        CommandBuilder::create('sort')
                            ->addFlag('r')
                            ->pipeOutputTo(
                                CommandBuilder::create('head')
                                    ->addFlag('1')
                            )
                    ),
                function ($output) use (&$fullOutput) {
                    $fullOutput .= $output;
                }
            )
            ->run();
        return $remote['releasesdir'] . '/' . substr($fullOutput, 0, (strpos($fullOutput, "\n") ?: strlen($fullOutput)));
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
    protected function backupTask(
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

        if ($opts['files']) {
            $collection
                ->taskRemoteFilesBackup($worker, $auth, $backupDir, $remote['filesdir'])
                    ->backupFile($this->backupFileName('.tar.gz'))
                    ->excludeFromBackup($this->excludeFromBackup)
                    ->backupSubDirs($this->fileBackupSubDirs)
                    ->timeout($this->getTimeoutSetting('backup_files'));
        }

        if ($opts['data']) {
            $currentProjectRoot = $this->getCurrentProjectRoot($worker, $auth, $remote);
            $collection
                ->taskRemoteDatabaseBackup($worker, $auth, $backupDir, $currentProjectRoot)
                    ->backupFile($this->backupFileName('.sql'))
                    ->timeout($this->getTimeoutSetting('backup_database'));
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
    protected function restoreBackupTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $opts = ['files' => false, 'data' => false]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }

        $currentProjectRoot = $this->getCurrentProjectRoot($worker, $auth, $remote);
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
                    ->timeout($this->getTimeoutSetting('restore_files_backup'))
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
                    ->timeout($this->getTimeoutSetting('restore_db_backup'))
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
            $removeFiles = CommandBuilder::create('rm')->addFlag('rf');
            if (!$this->fileBackupSubDirs) {
                $removeFiles->addArgument('./*');
                $removeFiles->addArgument('./.??*');
            }
            foreach ($this->fileBackupSubDirs as $subdir) {
                $removeFiles->addArgument($subdir . '/*');
                $removeFiles->addArgument($subdir . '/.??*');
            }

            return $this->taskSsh($worker, $auth)
                ->remoteDirectory($remote['filesdir'], true)
                // Files dir can be pretty big on large sites.
                ->timeout($this->getTimeoutSetting('pre_restore_remove_files'))
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
    protected function removeBackupTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $opts = ['files' => false, 'data' => false]
    ) {
        $backupDir = $remote['backupsdir'] . '/' . $remote['time'];

        $collection = $this->collectionBuilder();
        $collection->taskSsh($worker, $auth)
            ->timeout($this->getTimeoutSetting('remove_backup'))
            ->exec(
                (string) CommandBuilder::create('rm')
                    ->addFlag('rf')
                    ->addArgument($backupDir)
            );

        return $collection;
    }

    /**
     * Timeouts can be overwritten in properties.yml under the `timeout` key.
     *
     * @param string $setting
     *
     * @return int
     */
    protected function getTimeoutSetting($setting)
    {
        $timeoutSettings = $this->getTimeoutSettings();
        return isset($timeoutSettings[$setting]) ? $timeoutSettings[$setting] : static::DEFAULT_TIMEOUT;
    }

    protected function getTimeoutSettings()
    {
        $this->readProperties();
        return $this->getConfig()->get('timeouts', []) + $this->getDefaultTimeoutSettings();
    }

    protected function getDefaultTimeoutSettings()
    {
        // Refactor this to default.properties.yml
        return [
            'presymlink_mirror_dir' => 60,
            'synctask_rsync' => 1800,
            'backup_files' => 300,
            'backup_database' => 300,
            'remove_backup' => 300,
            'restore_files_backup' => 300,
            'restore_db_backup' => 60,
            'pre_restore_remove_files' => 300,
            'clean_dir' => 30,
            'clear_op_cache' => 30,
        ];
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
    protected function backupFileName($extension, $timestamp = null)
    {
        if (is_null($timestamp)) {
            $timestamp = $this->time;
        }
        return $timestamp . '_' . date('Y_m_d_H_i_s', $timestamp) . $extension;
    }
}

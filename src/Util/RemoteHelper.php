<?php

namespace DigipolisGent\Robo\Helpers\Util;

use Ckr\Util\ArrayMerger;
use Consolidation\Config\ConfigAwareTrait;
use Consolidation\Config\ConfigInterface;
use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\DependencyInjection\PropertiesHelperAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\PropertiesHelperAware;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use League\Container\ContainerAwareTrait;
use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\TaskAccessor;

class RemoteHelper implements BuilderAwareInterface, ConfigAwareInterface, PropertiesHelperAwareInterface
{
    use PropertiesHelperAware;
    use TaskAccessor;
    use \DigipolisGent\Robo\Task\General\Tasks;
    use \DigipolisGent\Robo\Task\Deploy\Tasks;
    use ConfigAwareTrait;
    use ContainerAwareTrait;

    public const DEFAULT_TIMEOUT = 10;

    public static $defaultEnvironmentOverrideSettings = [
        'environment_env_var' => 'HOSTNAME',
        'environment_matcher' => '\\DigipolisGent\\Robo\\Helpers\\Util\\EnvironmentMatcher::regexMatch',
    ];

    protected $time;

    protected $projectRoots = [];

    public function __construct(int $time, ConfigInterface $config, PropertiesHelper $propertiesHelper)
    {
        $this->time = $time;
        $this->setConfig($config);
        $this->setPropertiesHelper($propertiesHelper);
    }

    public static function create(DefinitionContainerInterface $container)
    {
        $object = new static(
            $container->get('digipolis.time'),
            $container->get('config'),
            $container->get(PropertiesHelper::class)
        );
        $object->setBuilder(CollectionBuilder::create($container, $object));

        return $object;
    }

    public function getTime()
    {
        return $this->time;
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
    public function getRemoteSettings($host, $user, $keyFile, $app, $timestamp = null)
    {
        $this->propertiesHelper->readProperties();
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
    public function getLocalSettings($app = null, $timestamp = null)
    {
        $this->propertiesHelper->readProperties();
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

    public function getCurrentProjectRoot($worker, AbstractAuth $auth, $remote)
    {
        $key = $worker . ':' . $auth->getUser() . ':' . $remote['releasesdir'];
        if (!array_key_exists($key, $this->projectRoots)) {
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
            $this->projectRoots[$key] = $remote['releasesdir'] . '/' . substr($fullOutput, 0, (strpos($fullOutput, "\n") ?: strlen($fullOutput)));
        }
        return $this->projectRoots[$key];
    }

    /**
     * Check if the current release has robo available.
     *
     * @param string $worker
     *   The server to check the release on.
     * @param \DigipolisGent\Robo\Helpers\Traits\AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return bool
     */
    public function currentReleaseHasRobo($worker, AbstractAuth $auth, $remote)
    {
        $currentProjectRoot = $this->getCurrentProjectRoot($worker, $auth, $remote);
        return $this->taskSsh($worker, $auth)
            ->remoteDirectory($currentProjectRoot, true)
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
     * Timeouts can be overwritten in properties.yml under the `timeout` key.
     *
     * @param string $setting
     *
     * @return int
     */
    public function getTimeoutSetting($setting)
    {
        $timeoutSettings = $this->getTimeoutSettings();
        return isset($timeoutSettings[$setting]) ? $timeoutSettings[$setting] : static::DEFAULT_TIMEOUT;
    }

    protected function getTimeoutSettings()
    {
        $this->propertiesHelper->readProperties();
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
}

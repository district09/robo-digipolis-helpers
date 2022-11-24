<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use Ckr\Util\ArrayMerger;
use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use DigipolisGent\Robo\Task\General\Common\DigipolisPropertiesAware;
use Robo\Contract\ConfigAwareInterface;

abstract class SettingsHandler extends AbstractTaskEventHandler implements ConfigAwareInterface
{
    use DigipolisPropertiesAware;
    use \DigipolisGent\Robo\Task\General\Tasks;
    use \DigipolisGent\Robo\Task\Deploy\Tasks;
    use \Consolidation\Config\ConfigAwareTrait;

    public static $defaultEnvironmentOverrideSettings = [
        'environment_env_var' => 'HOSTNAME',
        'environment_matcher' => '\\DigipolisGent\\Robo\\Helpers\\Util\\EnvironmentMatcher::regexMatch',
    ];

    /**
     * Process environment-specific overrides.
     *
     * @param array $settings
     * @return array
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
}

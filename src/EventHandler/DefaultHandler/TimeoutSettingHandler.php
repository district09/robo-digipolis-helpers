<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class TimeoutSettingHandler extends AbstractTaskEventHandler implements ConfigAwareInterface
{

    use \Consolidation\Config\ConfigAwareTrait;
    use \DigipolisGent\Robo\Task\General\Common\DigipolisPropertiesAware;
    use \DigipolisGent\Robo\Task\General\Tasks;

    public const DEFAULT_TIMEOUT = 10;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $type = $event->getArgument('type');
        $timeoutSettings = $this->getTimeoutSettings();
        return isset($timeoutSettings[$type]) ? $timeoutSettings[$type] : static::DEFAULT_TIMEOUT;
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

    /**
     * Get all timeout settings.
     *
     * @return array
     */
    protected function getTimeoutSettings()
    {
        $this->readProperties();
        return $this->getConfig()->get('timeouts', []) + $this->getDefaultTimeoutSettings();
    }

    /**
     * Get the default timeout settings.
     *
     * @return array
     */
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
            'pre_restore' => 300,
            'clean_dir' => 30,
            'clear_op_cache' => 30,
            'compress_old_release' => 300,
        ];
    }
}

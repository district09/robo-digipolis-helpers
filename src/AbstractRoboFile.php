<?php

namespace DigipolisGent\Robo\Helpers;

use DigipolisGent\Robo\Task\General\Common\DigipolisPropertiesAwareInterface;
use Robo\Contract\ConfigAwareInterface;

abstract class AbstractRoboFile extends \Robo\Tasks implements DigipolisPropertiesAwareInterface, ConfigAwareInterface
{
    use Traits\AbstractCommandTrait;
    use Traits\AbstractDeployCommandTrait;
    use Traits\AbstractSyncCommandTrait;
    use Traits\AbstractSyncRemoteCommandTrait;
    use Traits\DigipolisMirrorDirCommandTrait;
    use Traits\DigipolisRealpathCommandTrait;
    use Traits\DigipolisSwitchPreviousCommandTrait;
    use Traits\DigipolisSyncLocalCommandTrait;
    use Traits\RemoteRemoveReleaseTrait;

    const DEFAULT_TIMEOUT = 10;

    /**
     * File backup subdirs.
     *
     * @var string[]
     */
    protected $fileBackupSubDirs = [];

    /**
     * Files or directories to exclude from the backup.
     *
     * @var string[]
     */
    protected $excludeFromBackup = [];
}

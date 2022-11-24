<?php

namespace DigipolisGent\Robo\Helpers\EventHandler;

use DigipolisGent\Robo\Helpers\Util\TimeHelper;
use Robo\Contract\ConfigAwareInterface;

abstract class AbstractBackupHandler
    extends AbstractTaskEventHandler
    implements ConfigAwareInterface
{

    use \Consolidation\Config\ConfigAwareTrait;

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
    public function backupFileName($extension, $timestamp = null)
    {
        if (is_null($timestamp)) {
            $timestamp = TimeHelper::getInstance()->getTime();
        }
        return $timestamp . '_' . date('Y_m_d_H_i_s', $timestamp) . $extension;
    }
}

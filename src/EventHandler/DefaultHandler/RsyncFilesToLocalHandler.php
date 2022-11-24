<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use Symfony\Component\EventDispatcher\GenericEvent;

class RsyncFilesToLocalHandler extends AbstractTaskEventHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $localSettings = $event->getArgument('localSettings');
        $directory = $event->getArgument('directory');
        $fileBackupConfig = $event->getArgument('fileBackupConfig');

        $rsync = $this->taskRsync()
            ->rawArg('--rsh "ssh -o StrictHostKeyChecking=no -i `vendor/bin/robo digipolis:realpath ' . $remoteConfig->getPrivateKeyFile() . '`"')
            ->fromHost($remoteConfig->getHost())
            ->fromUser($remoteConfig->getUser())
            ->fromPath($remoteSettings['filesdir'] . '/' . $directory)
            ->toPath($localSettings['filesdir'] . '/' . $directory)
            ->archive()
            ->delete()
            ->rawArg('--copy-links --keep-dirlinks')
            ->compress()
            ->checksum()
            ->wholeFile();

        foreach ($fileBackupConfig['exclude_from_backup'] as $exclude) {
            $rsync->exclude($exclude);
        }

        return $rsync;
    }
}

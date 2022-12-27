<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Traits\RemoteRemoveReleaseTrait;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class RemoveFailedReleaseHandler extends AbstractTaskEventHandler
{

    use RemoteRemoveReleaseTrait;
    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        $releaseDir = $event->hasArgument('releaseDir')
            ? $event->getArgument('releaseDir')
            : $remoteSettings['releasesdir'] . '/' . $remoteSettings['time'];

        return $this->taskRemoteRemoveRelease($remoteConfig->getHost(), $auth, null, $releaseDir);
    }
}

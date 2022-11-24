<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class RemoteSwitchPreviousHandler extends AbstractTaskEventHandler
{
    use \DigipolisGent\Robo\Helpers\Traits\RemoteSwitchPreviousTrait;
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

        return $this->taskRemoteSwitchPrevious(
            $remoteConfig->getHost(),
            $auth,
            $remoteConfig->getCurrentProjectRoot(),
            $remoteSettings['releasesdir'],
            $remoteSettings['currentdir']
        );
    }
}

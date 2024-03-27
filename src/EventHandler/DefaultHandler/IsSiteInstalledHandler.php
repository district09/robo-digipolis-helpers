<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class IsSiteInstalledHandler extends AbstractTaskEventHandler
{

    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    protected $siteInstalled = null;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $event->stopPropagation();
        if (!is_null($this->siteInstalled)) {
            return $this->siteInstalled;
        }

        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $currentWebRoot = $remoteSettings['currentdir'];
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        $result = $this->taskSsh($remoteConfig->getHost(), $auth)
            ->remoteDirectory($currentWebRoot, true)
            ->exec('ls -al | grep index.php')
            ->run();
        $this->siteInstalled = $result->wasSuccessful();

        return $this->siteInstalled;
    }
}

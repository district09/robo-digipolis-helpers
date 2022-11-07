<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class CleanDirsHandler extends AbstractTaskEventHandler
{

    use \DigipolisGent\Robo\Helpers\Traits\RemoteCleanDirsTrait;
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

        $cleandirLimit = isset($remoteSettings['cleandir_limit']) ? max(1, $remoteSettings['cleandir_limit']) : '';
        $collection = $this->collectionBuilder();
        $collection->taskRemoteCleanDirs($remoteConfig->getHost(), $auth, $remoteSettings['rootdir'], $remoteSettings['releasesdir'], ($cleandirLimit ? ($cleandirLimit + 1) : false));

        if ($remoteSettings['createbackup']) {
            $collection->taskRemoteCleanDirs($remoteConfig->getHost(), $auth, $remoteSettings['rootdir'], $remoteSettings['backupsdir'], ($cleandirLimit ? ($cleandirLimit) : false));
        }

        return $collection;
    }
}

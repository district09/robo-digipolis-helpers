<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class PushPackageHandler extends AbstractTaskEventHandler
{

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
        $archive = $event->hasArgument('archiveName') && $event->getArgument('archiveName')
            ? $event->getArgument('archiveName')
            : $remoteSettings['time'] . '.tar.gz';
        $releaseDir = $remoteSettings['releasesdir'] . '/' . $remoteSettings['time'];

        $collection = $this->collectionBuilder();
        $collection->taskPushPackage($remoteConfig->getHost(), $auth)
            ->destinationFolder($releaseDir)
            ->package($archive);

        $collection->taskSsh($remoteConfig->getHost(), $auth)
            ->remoteDirectory($releaseDir, true)
            ->exec((string) CommandBuilder::create('chmod')
                ->addArgument('u+rx')
                ->addArgument('vendor/bin/robo')
            );

        return $collection;
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class RemoteSymlinkHandler extends PreSymlinkHandler
{
    use \DigipolisGent\Robo\Helpers\Traits\RemoteSwitchPreviousTrait;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $timeouts = $event->getArgument('timeouts');
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());

        $collection = $this->collectionBuilder();
        foreach ($remoteSettings['symlinks'] as $link) {
            $preIndividualSymlinkTask = $this->preIndividualSymlinkTask($remoteConfig, $link, $timeouts['symlink']);
            if ($preIndividualSymlinkTask) {
                $collection->addTask($preIndividualSymlinkTask);
            }
            list($target, $linkname) = explode(':', $link);
            $collection->taskSsh($remoteConfig->getHost(), $auth)
                ->exec(
                    (string) CommandBuilder::create('ln')
                        ->addFlag('s')
                        ->addFlag('T')
                        ->addFlag('f')
                        ->addArgument($target)
                        ->addArgument($linkname)
                );
        }

        return $collection;
    }
}

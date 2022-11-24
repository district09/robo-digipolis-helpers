<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class ClearRemoteOpcacheHandler extends AbstractTaskEventHandler
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
        $timeouts = $event->getArgument('timeouts');
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());

        $clearOpcache = CommandBuilder::create('vendor/bin/robo digipolis:clear-op-cache')->addArgument($remoteSettings['opcache']['env']);
        if (isset($remoteSettings['opcache']['host'])) {
            $clearOpcache->addOption('host', $remoteSettings['opcache']['host']);
        }

        return $this->taskSsh($remoteConfig->getHost(), $auth)
            ->remoteDirectory($remoteSettings['rootdir'], true)
            ->timeout($timeouts['clear_op_cache'])
            ->exec((string) $clearOpcache);
    }
}

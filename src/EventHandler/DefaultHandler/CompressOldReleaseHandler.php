<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class CompressOldReleaseHandler extends AbstractTaskEventHandler
{

    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument(('remoteConfig'));
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        $releaseToCompress = $event->getArgument('releaseToCompress');
        $timeouts = $event->getArgument('timeouts');

        // Strip the releases dir from the release to compress, so the tar
        // contains relative paths.
        $relativeReleaseToCompress = str_replace($remoteSettings['releasesdir'] . '/', '', $releaseToCompress);

        return $this->taskSsh($remoteConfig->getHost(), $auth)
            ->remoteDirectory($remoteSettings['releasesdir'])
            ->exec((string) CommandBuilder::create('tar')
                ->addFlag('c')
                ->addFlag('z')
                ->addFlag('f', $relativeReleaseToCompress . '.tar.gz')
                ->addArgument($relativeReleaseToCompress)
                ->onSuccess(
                    CommandBuilder::create('chown')
                        ->addFlag('R')
                        ->addArgument($remoteConfig->getUser() . ':' . $remoteConfig->getUser())
                        ->addArgument($relativeReleaseToCompress)
                        ->onSuccess(CommandBuilder::create('chmod')
                            ->addFlag('R')
                            ->addArgument('a+rwx')
                            ->addArgument($relativeReleaseToCompress)
                            ->onSuccess(CommandBuilder::create('rm')
                                ->addFlag('rf')
                                ->addArgument($relativeReleaseToCompress)
                            )
                        )
                )
                ->onFailure(
                    CommandBuilder::create('rm')
                        ->addFlag('r')
                        ->addFlag('f')
                        ->addArgument($relativeReleaseToCompress . '.tar.gz')
                )
            )->timeout($timeouts['compress_old_release']);
    }
}

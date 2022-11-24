<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class PostSymlinkHandler extends AbstractTaskEventHandler
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
        $timeouts = $event->getArgument('timeouts');

        $collection = $this->collectionBuilder();
        if (isset($remoteSettings['postsymlink_filechecks']) && $remoteSettings['postsymlink_filechecks']) {
            $projectRoot = $remoteSettings['rootdir'];
            $collection->taskSsh($remoteConfig->getHost(), $auth)
                ->remoteDirectory($projectRoot, true)
                ->timeout($timeouts['post_symlink']);
            foreach ($remoteSettings['postsymlink_filechecks'] as $file) {
                // If this command fails, the collection will fail, which will
                // trigger a rollback.
                $builder = CommandBuilder::create('ls')
                    ->addArgument($file)
                    ->pipeOutputTo('grep')
                    ->addArgument($file)
                    ->onFailure(
                        CommandBuilder::create('echo')
                            ->addArgument('[ERROR] ' . $file . ' was not found.')
                            ->onFinished('exit')
                            ->addArgument('1')
                    );
                $collection->exec((string) $builder);
            }
        }

        return $collection;
    }
}

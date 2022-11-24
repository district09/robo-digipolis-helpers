<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class PreSymlinkHandler extends AbstractTaskEventHandler
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
        foreach ($remoteSettings['symlinks'] as $symlink) {
            $preIndividualSymlinkTask = $this->preIndividualSymlinkTask($remoteConfig, $symlink, $timeouts['pre_symlink']);
            if ($preIndividualSymlinkTask) {
                $collection->addTask($preIndividualSymlinkTask);
            }
        }

        return $collection;
    }

    /**
     * Tasks to execute before creating an individual symlink.
     *
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     * @param string $symlink
      *  The symlink in format "target:link".
     * @param int $timeout
     *   The SSH timeout in seconds.
     *
     * @return bool|\Robo\Contract\TaskInterface
     *   The presymlink task, false if no pre symlink task needs to run.
     */
    public function preIndividualSymlinkTask(RemoteConfig $remoteConfig, $symlink, $timeout)
    {
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $projectRoot = $remoteSettings['rootdir'];
        $task = $this->taskSsh($remoteConfig->getHost(), new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile()))
            ->remoteDirectory($projectRoot, true)
            ->timeout($timeout);
        list($target, $link) = explode(':', $symlink);
        if ($link === $remoteSettings['currentdir']) {
            return false;
        }
        // If the link we're going to create is an existing directory,
        // mirror that directory on the symlink target and then delete it
        // before creating the symlink
        $task->exec(
            (string) CommandBuilder::create('vendor/bin/robo digipolis:mirror-dir')
                ->addArgument($link)
                ->addArgument($target)
        );
        $task->exec(
            (string) CommandBuilder::create('rm')
                ->addFlag('rf')
                ->addArgument($link)
        );

        return $task;
    }
}

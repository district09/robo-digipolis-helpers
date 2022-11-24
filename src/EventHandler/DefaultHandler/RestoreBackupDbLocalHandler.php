<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractBackupHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use Symfony\Component\EventDispatcher\GenericEvent;

class RestoreBackupDbLocalHandler extends AbstractBackupHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $dbBackupFile =  $this->backupFileName('.sql.gz', $remoteSettings['time']);
        $dbRestore = CommandBuilder::create('vendor/bin/robo digipolis:database-restore')->addOption('source', $dbBackupFile);
        $cwd = getcwd();

        return $this->taskExecStack()
            ->exec(
                (string) CommandBuilder::create('cd')
                    ->addArgument($this->getConfig()->get('digipolis.root.project'))
                    ->onSuccess($dbRestore)
            )
            ->exec(
                (string) CommandBuilder::create('cd')
                    ->addArgument($cwd)
                    ->onSuccess(
                        CommandBuilder::create('rm')
                            ->addFlag('rf')
                            ->addArgument($dbBackupFile)
                    )
            );
    }
}

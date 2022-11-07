<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractBackupHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class PreRestoreBackupRemoteHandler extends AbstractBackupHandler
{

    use \DigipolisGent\Robo\Helpers\Traits\RemoteDatabaseBackupTrait;
    use \DigipolisGent\Robo\Helpers\Traits\RemoteFilesBackupTrait;
    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $options = $event->getArgument('options');
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        $timeouts = $event->getArgument('timeouts');
        $fileBackupConfig = $event->getArgument('fileBackupConfig');

        if (!$options['files'] && !$options['data']) {
            $options['files'] = true;
            $options['data'] = true;
        }
        if ($options['files']) {
            $removeFiles = CommandBuilder::create('rm')->addFlag('rf');
            if (!$fileBackupConfig['file_backup_subdirs']) {
                $removeFiles->addArgument('./*');
                $removeFiles->addArgument('./.??*');
            }
            foreach ($fileBackupConfig['file_backup_subdirs'] as $subdir) {
                $removeFiles->addArgument($subdir . '/*');
                $removeFiles->addArgument($subdir . '/.??*');
            }

            return $this->taskSsh($remoteConfig->getHost(), $auth)
                ->remoteDirectory($remoteSettings['filesdir'], true)
                // Files dir can be pretty big on large sites.
                ->timeout($timeouts['pre_restore'])
                ->exec((string) $removeFiles);
        }

        return $this->collectionBuilder();
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class RsyncFilesBetweenHostsHandler extends AbstractTaskEventHandler
{

    use \DigipolisGent\Robo\Task\Deploy\Tasks;
    use \Robo\Task\Base\Tasks;
    use \Robo\Task\Remote\Tasks;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $sourceRemoteConfig */
        $sourceRemoteConfig = $event->getArgument('sourceRemoteConfig');
        $destinationRemoteConfig = $event->getArgument('destinationRemoteConfig');
        $fileBackupConfig = $event->getArgument('fileBackupConfig');
        $timeouts = $event->getArgument('timeouts');

        $tmpPrivateKeyFile = '~/.ssh/' . uniqid('robo_', true) . '.id_rsa';
        $collection = $this->collectionBuilder();
        // Generate a temporary key.
        $collection->addTask(
            $this->generateKeyPair($tmpPrivateKeyFile)
        );

        $collection->completion(
            $this->removeKeyPair($tmpPrivateKeyFile)
        );

        // Install it on the destination host.
        $collection->addTask(
            $this->installPublicKeyOnDestination(
                $tmpPrivateKeyFile,
                $destinationRemoteConfig
            )
        );

        // Remove it from the destination host when we're done.
        $collection->completion(
            $this->removePublicKeyFromDestination(
                $tmpPrivateKeyFile,
                $destinationRemoteConfig
            )
        );

        // Install the private key on the source host.
        $collection->addTask(
            $this->installPrivateKeyOnSource(
                $tmpPrivateKeyFile,
                $sourceRemoteConfig
            )
        );

        // Remove the private key from the source host.
        $collection->completion(
            $this->removePrivateKeyFromSource(
                $tmpPrivateKeyFile,
                $sourceRemoteConfig
            )
        );

        $dirs = ($fileBackupConfig['file_backup_subdirs'] ? $fileBackupConfig['file_backup_subdirs'] : ['']);

        foreach ($dirs as $dir) {
            $dir .= ($dir !== '' ? '/' : '');
            $collection->addTask(
                $this->rsyncDirectory(
                    $dir,
                    $tmpPrivateKeyFile,
                    $sourceRemoteConfig,
                    $destinationRemoteConfig,
                    $fileBackupConfig,
                    $timeouts['synctask_rsync']
                )
            );
        }

        return $collection;
    }


    /**
     * Generate an SSH key pair.
     *
     * @param string $privateKeyFile
     *   Path to store the private key file.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function generateKeyPair($privateKeyFile)
    {
        return $this->taskExec(
            (string) CommandBuilder::create('ssh-keygen')
                ->addFlag('q')
                ->addFlag('t', 'rsa')
                ->addFlag('b', 4096)
                ->addRawFlag('N', '""')
                ->addRawFlag('f', $privateKeyFile)
                ->addFlag('C', 'robo:' . md5($privateKeyFile))
        );
    }

    /**
     * Remove an SSH key pair.
     *
     * @param string $privateKeyFile
     *   Path to store the private key file.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function removeKeyPair($privateKeyFile)
    {
        return $this->taskExecStack()
            ->exec(
                (string) CommandBuilder::create('rm')
                    ->addFlag('f')
                    ->addRawArgument($privateKeyFile)
                    ->addRawArgument($privateKeyFile . '.pub')
            );
    }

    /**
     * Install a public SSH key on a host.
     *
     * @param string $privateKeyFile
     *   Path to the private key file of the key pair to install.
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function installPublicKeyOnDestination($privateKeyFile, RemoteConfig $remoteConfig)
    {
        return $this->taskExec(
            (string) CommandBuilder::create('cat')
                ->addRawArgument($privateKeyFile . '.pub')
                ->pipeOutputTo(
                    CommandBuilder::create('ssh')
                        ->addArgument($remoteConfig->getUser() . '@' . $remoteConfig->getHost())
                        ->addFlag('o', 'StrictHostKeyChecking=no')
                        ->addRawFlag('i', $remoteConfig->getPrivateKeyFile())
                )
                ->addArgument(
                    CommandBuilder::create('mkdir')
                        ->addFlag('p')
                        ->addRawArgument('~/.ssh')
                        ->onSuccess(
                            CommandBuilder::create('cat')
                                ->chain('~/.ssh/authorized_keys', '>>')
                        )
                )
        );
    }

    /**
     * Remove a public key from a host.
     *
     * @param string $privateKeyFile
     *   Path to the private key file of the key pair to remove.
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function removePublicKeyFromDestination($privateKeyFile, RemoteConfig $remoteConfig)
    {
        return $this->taskSsh($remoteConfig->getHost(), new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile()))
            ->exec(
                (string) CommandBuilder::create('sed')
                    ->addFlag('i', '/robo:' . md5($privateKeyFile) . '/d')
                    ->addRawArgument('~/.ssh/authorized_keys')
            );
    }

    /**
     * Install a private key on a host.
     *
     * @param string $privateKeyFile
     *   Private key to install.
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function installPrivateKeyOnSource($privateKeyFile, RemoteConfig $remoteConfig)
    {
        return $this->taskRsync()
            ->rawArg('--rsh "ssh -o StrictHostKeyChecking=no -i `vendor/bin/robo digipolis:realpath ' . $remoteConfig->getPrivateKeyFile() . '`"')
            ->fromPath($privateKeyFile)
            ->toHost($remoteConfig->getHost())
            ->toUser($remoteConfig->getUser())
            ->toPath('~/.ssh')
            ->archive()
            ->compress()
            ->checksum()
            ->wholeFile();
    }

    /**
     * Remove a private key from a host.
     *
     * @param string $privateKeyFile
     *   Path to the private key file of the key pair to remove.
     * @param RemoteConfig $remoteConfig
     *   RemoteConfig object populated with data relevant to the host.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function removePrivateKeyFromSource($privateKeyFile, RemoteConfig $remoteConfig)
    {
        return $this->taskSsh($remoteConfig->getHost(), new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile()))
            ->exec(
                (string) CommandBuilder::create('rm')
                    ->addFlag('f')
                    ->addRawArgument($privateKeyFile)
            );
    }

    /**
     * Rsync a directory between hosts.
     *
     * @param string $directory
     *   The directory to sync.
     * @param string $privateKeyFile
     *   The path the the private key of the keypair installed on src and dest.
     * @param RemoteConfig $sourceRemoteConfig
     *   RemoteConfig object populated with data relevant to the source.
     * @param RemoteConfig $destinationRemoteConfig
     *   RemoteConfig object populated with data relevant to the destination.
     * @param array $fileBackupConfig
     *   File backup config.
     * @param int $timeout
     *   Timeout setting for the sync.
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function rsyncDirectory(
        $directory,
        $privateKeyFile,
        RemoteConfig $sourceRemoteConfig,
        RemoteConfig $destinationRemoteConfig,
        $fileBackupConfig,
        $timeout
    ) {
        $sourceRemoteSettings = $sourceRemoteConfig->getRemoteSettings();
        $destinationRemoteSettings = $destinationRemoteConfig->getRemoteSettings();
        $rsync = $this->taskRsync()
            ->rawArg('--rsh "ssh -o StrictHostKeyChecking=no -i `cd -P ' . $sourceRemoteConfig->getCurrentProjectRoot() . ' && vendor/bin/robo digipolis:realpath ' . $privateKeyFile . '`"')
            ->fromPath($sourceRemoteSettings['filesdir'] . '/' . $directory)
            ->toHost($destinationRemoteConfig->getHost())
            ->toUser($destinationRemoteConfig->getUser())
            ->toPath($destinationRemoteSettings['filesdir'] . '/' . $directory)
            ->archive()
            ->delete()
            ->rawArg('--copy-links --keep-dirlinks')
            ->compress()
            ->checksum()
            ->wholeFile();
        foreach ($fileBackupConfig['exclude_from_backup'] as $exclude) {
            $rsync->exclude($exclude);
        }

        return $this->taskSsh($sourceRemoteConfig->getHost(), new KeyFile($sourceRemoteConfig->getUser(), $sourceRemoteConfig->getPrivateKeyFile()))
            ->timeout($timeout)
            ->exec($rsync);
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use DigipolisGent\Robo\Helpers\Traits\CommandWithBackups;
use DigipolisGent\Robo\Helpers\Traits\DigipolisHelpersCommandUtilities;
use DigipolisGent\Robo\Helpers\Traits\EventDispatcher;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use Robo\Tasks;

class DigipolisHelpersSyncLocalCommand extends Tasks implements CustomEventAwareInterface
{
    use CustomEventAwareTrait;
    use EventDispatcher;
    use DigipolisHelpersCommandUtilities;
    use CommandWithBackups;

    /**
     * Sync the database and files to your local environment.
     *
     * @param string $host
     *   IP address of the source server.
     * @param string $user
     *   SSH user to connect to the source server.
     * @param string $privateKeyFile
     *   Private key file to use to connect to the source server.
     * @param array $opts
     *   Command options
     *
     * @return \Robo\Contract\TaskInterface
     *   The sync task.
     *
     *
     * @option app The name of the app we're syncing.
     * @option files Sync only files.
     * @option data Sync only the database.
     * @option rsync Sync the files via rsync.
     *
     * @command digipolis:sync-local
     */
    public function digipolisSyncLocal(
        $host,
        $user,
        $privateKeyFile,
        $opts = [
            'app' => 'default',
            'files' => false,
            'data' => false,
            'rsync' => true,
        ]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }

        $opts['rsync'] = !isset($opts['rsync']) || $opts['rsync'];

        $remoteSettings = $this->getRemoteSettings($host, $user, $privateKeyFile, $opts['app']);
        $currentProjectRoot = $this->getCurrentProjectRoot($host, $user, $privateKeyFile, $remoteSettings);
        $remoteConfig = new RemoteConfig($host, $user, $privateKeyFile, $remoteSettings, $currentProjectRoot);
        $localSettings = $this->getLocalSettings($opts['app']);
        $collection = $this->collectionBuilder();

        if ($opts['files']) {
            $collection->addTask($this->handleTaskEvent(
                'digipolis:pre-local-sync-files',
                [
                    'localSettings' => $localSettings,
                    'remoteConfig' => $remoteConfig,
                ]
            ));

            $fileBackupConfig = $this->getFileBackupConfig();
            if ($opts['rsync']) {
                $opts['files'] = false;
                $dirs = ($fileBackupConfig['file_backup_subdirs'] ? $fileBackupConfig['file_backup_subdirs'] : ['']);

                foreach ($dirs as $dir) {
                    $dir .= ($dir !== '' ? '/' : '');
                    $collection->addTask($this->handleTaskEvent(
                        'digipolis:rsync-files-to-local',
                        [
                            'remoteConfig' => $remoteConfig,
                            'localSettings' => $localSettings,
                            'directory' => $dir,
                            'fileBackupConfig' => $fileBackupConfig,
                        ]
                    ));
                }
            }
        }

        if ($opts['data'] || $opts['files']) {
            // Create the backup on the server.
            $collection->addTask($this->backupRemoteTask($remoteConfig, $opts));

            // Download the backup.
            $collection->addTask($this->downloadBackupTask($remoteConfig, $opts));
        }

        if ($opts['files']) {
            // Restore the files backup.
            $collection->addTask($this->handleTaskEvent(
                'digipolis:restore-backup-files-local',
                [
                    'remoteConfig' => $remoteConfig,
                    'localSettings' => $localSettings,
                ]
            ));

        }

        if ($opts['data']) {
            // Restore the db backup.
            $collection->addTask($this->handleTaskEvent(
                'digipolis:restore-backup-db-local',
                [
                    'remoteConfig' => $remoteConfig,
                    'localSettings' => $localSettings,
                ]
            ));
        }

        return $collection;
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use DigipolisGent\Robo\Helpers\Traits\CommandWithBackups;
use DigipolisGent\Robo\Helpers\Traits\DigipolisHelpersCommandUtilities;
use DigipolisGent\Robo\Helpers\Traits\EventDispatcher;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use Robo\Tasks;

class DigipolisHelpersSyncCommand extends Tasks implements CustomEventAwareInterface
{
   use \DigipolisGent\Robo\Helpers\Traits\DigipolisHelpersSyncCommandUtilities;

   /**
     * Sync the database and files between two sites.
     *
     * @param string $sourceUser
     *   SSH user to connect to the source server.
     * @param string $sourceHost
     *   IP address of the source server.
     * @param string $sourcePrivateKeyFile
     *   Private key file to use to connect to the source server.
     * @param string $destinationUser
     *   SSH user to connect to the destination server.
     * @param string $destinationHost
     *   IP address of the destination server.
     * @param string $destinationPrivateKeyFile
     *   Private key file to use to connect to the destination server.
     * @param string $sourceApp
     *   The name of the source app we're syncing. Used to determine the
     *   directory to sync.
     * @param string $destinationApp
     *   The name of the destination app we're syncing. Used to determine the
     *   directory to sync to.
     *
     * @return \Robo\Contract\TaskInterface
     *   The sync task.
     *
     * @command digipolis:sync
     */
    public function syncCommand(
        $sourceUser,
        $sourceHost,
        $sourcePrivateKeyFile,
        $destinationUser,
        $destinationHost,
        $destinationPrivateKeyFile,
        $sourceApp = 'default',
        $destinationApp = 'default',
        $opts = ['files' => false, 'data' => false, 'rsync' => true]
    ) {
        return $this->sync(
            $sourceUser,
            $sourceHost,
            $sourcePrivateKeyFile,
            $destinationUser,
            $destinationHost,
            $destinationPrivateKeyFile,
            $sourceApp,
            $destinationApp,
            $opts
        );
    }
}

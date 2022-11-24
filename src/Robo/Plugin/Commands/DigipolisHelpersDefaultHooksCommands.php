<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Commands;

use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\BackupRemoteHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\BuildTaskHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\CleanDirsHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\ClearCacheHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\ClearRemoteOpcacheHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\CompressOldReleaseHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\CurrentProjectRootHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\DownloadBackupHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\InstallHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\LocalSettingsHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\MirrorDirHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\PostSymlinkHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\PreLocalSyncFilesHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\PreRestoreBackupRemoteHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\PreSymlinkHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\PushPackageHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RealpathHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RemoteSettingsHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RemoteSwitchPreviousHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RemoteSymlinkHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RemoveBackupRemoteHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RemoveFailedReleaseHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RemoveLocalBackupHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RestoreBackupDbLocalHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RestoreBackupFilesLocalHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RestoreBackupRemoteHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RsyncFilesBetweenHostsHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\RsyncFilesToLocalHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\SwitchPreviousHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\TimeoutSettingHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\UpdateHandler;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\UploadBackupHandler;
use Robo\Tasks;

/**
 * Default hook implementations.
 *
 * For robo to parse this file, the classname must end in "commands", even
 * though it doesn't actually contain any commands, only hook implementations.
 */
class DigipolisHelpersDefaultHooksCommands extends Tasks
{

    /**
     * Default implementation for the digipolis:mirror-dir command.
     *
     * @hook on-event digipolis:mirror-dir
     */
    public function getMirrorDirHandler() {
        return new MirrorDirHandler();
    }

    /**
     * Default implementation for the digipolis:realpath command.
     *
     * @hook on-event digipolis:realpath
     */
    public function getRealpathHandler() {
        return new RealpathHandler();
    }

    /**
     * Default implementation for the digipolis:switch-previous command.
     *
     * @hook on-event digipolis:switch-previous
     */
    public function getSwitchPreviousHandler() {
        return new SwitchPreviousHandler();
    }

    /**
     * Default implementation for the digipolis:get-remote-settings task.
     *
     * @hook on-event digipolis:get-remote-settings
     */
    public function getRemoteSettingsHandler() {
        return new RemoteSettingsHandler();
    }

    /**
     * Default implementation for the digipolis:get-local-settings task.
     *
     * @hook on-event digipolis:get-local-settings
     */
    public function getLocalSettingsHandler() {
        return new LocalSettingsHandler();
    }

    /**
     * Default implementation for the digipolis:get-local-settings task.
     *
     * @hook on-event digipolis:pre-local-sync-files
     */
    public function getPreLocalSyncFilesHandler() {
        return new PreLocalSyncFilesHandler();
    }

    /**
     * Default implementation for the digipolis:rsync-files-to-local task.
     *
     * @hook on-event digipolis:rsync-files-to-local
     */
    public function getRsyncFilesToLocalHandler()
    {
        return new RsyncFilesToLocalHandler();
    }

    /**
     * Default implementation for the digipolis:backup-remote task.
     *
     * @hook on-event digipolis:backup-remote
     */
    public function getBackupRemoteHandler()
    {
        return new BackupRemoteHandler();
    }

    /**
     * Default implementation for the digipolis:download-backup task.
     *
     * @hook on-event digipolis:download-backup
     */
    public function getDownloadBackupHandler()
    {
        return new DownloadBackupHandler();
    }

    /**
     * Default implementation for the digipolis:upload-backup task.
     *
     * @hook on-event digipolis:upload-backup
     */
    public function getUploadBackupHandler()
    {
        return new UploadBackupHandler();
    }

    /**
     * Default implementation for the digipolis:restore-backup-files-local task.
     *
     * @hook on-event digipolis:restore-backup-files-local
     */
    public function getRestoreBackupFilesLocalHandler()
    {
        return new RestoreBackupFilesLocalHandler();
    }

    /**
     * Default implementation for the digipolis:remove-local-backup task.
     *
     * @hook on-event digipolis:remove-local-backup
     */
    public function getRemoveLocalBackupHandler()
    {
        return new RemoveLocalBackupHandler();
    }

    /**
     * Default implementation for the digipolis:restore-backup-db-local task.
     *
     * @hook on-event digipolis:restore-backup-db-local
     */
    public function getRestoreBackupDbLocalHandler()
    {
        return new RestoreBackupDbLocalHandler();
    }

    /**
     * Default implementation for the digipolis:timeout-setting task.
     *
     * @hook on-event digipolis:timeout-setting
     */
    public function getTimeoutSettingHandler()
    {
        return new TimeoutSettingHandler();
    }

    /**
     * Default implementation for the digipolis:current-project-root task.
     *
     * @hook on-event digipolis:current-project-root
     */
    public function getCurrentProjectRootHandler()
    {
        return new CurrentProjectRootHandler();
    }

    /**
     * Default implementation for the digipolis:build-task task.
     *
     * @hook on-event digipolis:build-task
     */
    public function getBuildTaskHandler()
    {
        return new BuildTaskHandler();
    }

    /**
     * Default implementation for the digipolis:restore-backup-remote task.
     *
     * @hook on-event digipolis:restore-backup-remote
     */
    public function getRestoreBackupRemoteHandler()
    {
        return new RestoreBackupRemoteHandler();
    }

    /**
     * Default implementation for the digipolis:pre-restore-backup-remote task.
     *
     * @hook on-event digipolis:pre-restore-backup-remote
     */
    public function getPreRestoreBackupRemoteHandler()
    {
        return new PreRestoreBackupRemoteHandler();
    }

    /**
     * Default implementation for the digipolis:remove-failed-release task.
     *
     * @hook on-event digipolis:remove-failed-release
     */
    public function getRemoveFailedReleaseHandler()
    {
        return new RemoveFailedReleaseHandler();
    }

    /**
     * Default implementation for the digipolis:clear-remote-opcache task.
     *
     * @hook on-event digipolis:clear-remote-opcache
     */
    public function getClearRemoteOpcacheHandler()
    {
        return new ClearRemoteOpcacheHandler();
    }

    /**
     * Default implementation for the digipolis:push-package task.
     *
     * @hook on-event digipolis:push-package
     */
    public function getPushPackageHandler()
    {
        return new PushPackageHandler();
    }

    /**
     * Default implementation for the digipolis:pre-symlink task.
     *
     * @hook on-event digipolis:pre-symlink
     */
    public function getPreSymlinkHandler()
    {
        return new PreSymlinkHandler();
    }

    /**
     * Default implementation for the digipolis:remote-switch-previous task.
     *
     * @hook on-event digipolis:remote-switch-previous
     */
    public function getRemoteSwitchPreviousHandler()
    {
        return new RemoteSwitchPreviousHandler();
    }

    /**
     * Default implementation for the digipolis:remote-symlinks task.
     *
     * @hook on-event digipolis:remote-symlink
     */
    public function getRemoteSymlinkHandler()
    {
        return new RemoteSymlinkHandler();
    }

    /**
     * Default implementation for the digipolis:post-symlink task.
     *
     * @hook on-event digipolis:post-symlink
     */
    public function getPostSymlinkHandler()
    {
        return new PostSymlinkHandler();
    }

    /**
     * Default implementation for the digipolis:install task.
     *
     * @hook on-event digipolis:install
     */
    public function getInstallHandler()
    {
        return new InstallHandler();
    }

    /**
     * Default implementation for the digipolis:update task.
     *
     * @hook on-event digipolis:update
     */
    public function getUpdateHandler()
    {
        return new UpdateHandler();
    }

    /**
     * Default implementation for the digipolis:clear-cache task.
     *
     * @hook on-event digipolis:clear-cache
     */
    public function getClearCacheHandler()
    {
        return new ClearCacheHandler();
    }

    /**
     * Default implementation for the digipolis:compress-old-releases task.
     *
     * @hook on-event digipolis:compress-old-release
     */
    public function getCompressOldReleaseHandler()
    {
        return new CompressOldReleaseHandler();
    }

    /**
     * Default implementation for the digipolis:clean-dirs task.
     *
     * @hook on-event digipolis:clean-dirs
     */
    public function getCleanDirsHandler()
    {
        return new CleanDirsHandler();
    }

    /**
     * Default implementation for the digipolis:rsync-files-between-hosts task.
     *
     * @hook on-event digipolis:rsync-files-between-hosts
     */
    public function getRsyncFilesBetweenHostsHandler()
    {
        return new RsyncFilesBetweenHostsHandler();
    }

    /**
     * Default implementation for the digipolis:remove-backup-remote task.
     *
     * @hook on-event digipolis:remove-backup-remote
     */
    public function getRemoveBackupRemoteHandler()
    {
        return new RemoveBackupRemoteHandler();
    }
}

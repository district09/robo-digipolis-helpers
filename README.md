# Robo Digipolis Helpers

[![Latest Stable Version](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/v/stable)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)
[![Latest Unstable Version](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/v/unstable)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)
[![Total Downloads](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/downloads)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)
[![License](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/license)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)

[![Build Status](https://travis-ci.org/digipolisgent/robo-digipolis-helpers.svg?branch=develop)](https://travis-ci.org/digipolisgent/robo-digipolis-helpers)
[![Maintainability](https://api.codeclimate.com/v1/badges/1c4c5693cb7945f5e5e9/maintainability)](https://codeclimate.com/github/digipolisgent/robo-digipolis-helpers/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/1c4c5693cb7945f5e5e9/test_coverage)](https://codeclimate.com/github/digipolisgent/robo-digipolis-helpers/test_coverage)


Used by digipolis, generic commands/skeleton do execute deploys and syncs between environments.


## Getting started

To let this package know about your database configuration, please read
[the robo-digipolis-deploy package's documentation]
(https://github.com/district09/robo-digipolis-deploy#use-events-for-default-configuration).

We make a couple of assumptions, most of which can be overwritten. See
[default.properties.yml](src/default.properties.yml) for all default values, and
[the properties.yml documentation](#propertiesyml) for all available
configuration options.

By default, we assume a [capistrano-like directory structure](http://capistranorb.com/documentation/getting-started/structure/)
on your servers:
```
├── ~/apps/[app]/current -> ~/apps/[app]/releases/20150120114500/
├── ~/apps/[app]/releases
│   ├── 20150080072500
│   ├── 20150090083000
│   ├── 20150100093500
│   ├── 20150110104000
│   └── 20150120114500
```

This package provides a couple of commands. You can use `vendor/bin/robo list`
and `vendor/bin/robo help [command]` to find out what they do. Most importantly
these commands follow a "skeleton", in which each step of the command fires an
event, and the event listeners return an
[EventHandlerWithPriority](src/EventHandler/EventHandlerWithPriority). The
default event listeners provided by this package are in the
[DigipolisHelpersDefaultHooksCommands](src/Robo/Plugin/Commands/DigipolisHelpersDefaultHooksCommands)
class. Each method of that class is an event listener, and returns an event
handler. The default handlers provided by this package can be found in
[src/EventHandler/DefaultHandler](src/EventHandler/DefaultHandler). If you want
to overwrite or alter the behavior of a certain step in the command, all you
have to do is
[create an event listener by using the on-event hook](https://github.com/consolidation/annotated-command#on-event-hook)
for the right event, and let it return your custom handler. Handlers are
executed in order of priority (lower numbers executed first), the priority of
default handlers is 999. If your handler calls `$event->stopPropagation()` in
its `handle` method, handlers that come after it, won't get executed. For
further information, see the
[list of available events](#list-of-available-events);

### properties.yml

You need to provide a `properties.yml` file in your project root as well, so
the script knows what symlinks to create, where to put backups, ...

Below is an example of some sensible defaults:

```YAML
remote:
  # The application directory where your capistrano folder structure resides.
  appdir: '/home/[user]/apps/[app]'
  # The releases directory where to deploy new releases to.
  releasesdir: '${remote.appdir}/releases'
  # The root directory of a new release.
  rootdir: '${remote.releasesdir}/[time]'
  # The web directory of a new release (where e.g. your index.php file resides).
  webdir: '${remote.rootdir}/public'
  # The directory of your current release (your web root). Usually a symlink,
  # see `symlinks` below.
  currentdir: '${remote.appdir}/current'
  # The directory where your config files reside. This should get symlinked to
  # since your config files are normally not in your git repository, and shared
  # across releases. See `symlinks` below.
  configdir: '${remote.appdir}/config'
  # Your files directory that is used to store files uploaded by end-users. This
  # should get symlinked to since the uploaded files are shared across releases.
  # See `symlinks` below.
  filesdir: '${remote.appdir}/files'
  # Whether or not to create a backup (and corresponding restore task on
  # rollback during deploy.
  createbackup: true
  # The folder where to store backups that are created before running updates.
  backupsdir: '${remote.appdir}/backups'
  # The symlinks to create when deploying, in format target:link
  symlinks:
    # Symlink the webdir of the release we're creating to the current directory.
    - '${remote.webdir}:${remote.currentdir}'
    # Symlink the config file from within our config directory to the config of
    # our current release.
    - '${remote.configdir}/config.php:${remote.rootdir}/config/config.php'
  # After a successful release, older releases are removed from
  # ${remote.releasesdir}. This value determines how many past releases we
  # should keep.
  cleandir_limit: 5
  # To save some space/inodes on your server you can choose to compress the
  # older releases that are not being removed. This way they are still available
  # if you need to do a manual rollback, but don't take up as much space/inodes.
  compress_old_releases: false
  # We allow overriding settings under the `remote` key in properties.yml by
  # environment. This means we need to have a reliable way to determine which
  # environment we're currently on. We use the value of an environment variable
  # to determine the environment. This variable defaults to HOSTNAME.
  environment_env_var: HOSTNAME
  # We use an environment matcher to match the value of the environment variable
  # mentioned above, to the overrides that need to be applied (see
  # `environment_overrides` below. This needs to be a PHP callable, and defaults
  # to `\DigipolisGent\Robo\Helpers\Util\EnvironmentMatcher::regexMatch` to
  # match the value by using a regular expression.
  # `\DigipolisGent\Robo\Helpers\Util\EnvironmentMatcher::literalMatch` is also
  # available to match by a literal value. You can of course also implement your
  # own.
  environment_matcher: '\DigipolisGent\Robo\Helpers\Util\EnvironmentMatcher::regexMatch'
  # Here you can specify the overrides per evironment. This example uses the
  # default regex matcher.
  environment_overrides:
    # List your overrides here.
    # The keys will be matched against the value of `environment_env_var` via
    # `environment_matcher`.
    # The default properties use regexMatch and thus the keys here are PCRE
    # regexes.
    # The values per key can be anything set in `remote` and will override that
    # setting.
    # All matches are used (top to bottom).
    ^qa:
      cleandir_limit: 3
    ^staging:
      cleandir_limit: 2
# We use the phpseclib library to execute our ssh commands. Their default
# timeout is 10 seconds. Some tasks take longer than that, so we make the
# timeouts configurable. Below are the configurable timeouts. The values used in
# this example are the defaults, meaning that if you don't add them to your
# properties.yml, these are the values that will be used.
timeouts:
  # When we symlink a directory, but it already exists as a directory where we
  # are going to put the symlink, we mirror that directory to the target before
  # removing it and replacing it with the symlink. This is the timeout for the
  # mirroring of such a directory.
  presymlink_mirror_dir: 60
  # We provide a task for syncing database and files between
  # servers/environments. This is the timeout for the rsync command to sync the
  # files in ${remote.filesdir}.
  synctask_rsync: 1800
  # The timeout to create a backup of ${remote.filesdir}.
  backup_files: 300
  # The timeout to create a database backup.
  backup_database: 300
  # The timeout to remove a backup of both files and database (during sync, a
  # backup is created, restored on the destination and then removed from the
  # source).
  remove_backup: 300
  # The timeout to restore a files backup.
  restore_files_backup: 300
  # The timeout to restore a database backup.
  restore_db_backup: 60
  # Before a files backup is restored, the current files are removed. This is
  # the timeout for removing those files.
  pre_restore: 300
  # See ${remote.cleandir_limit}. This is the timeout for that operation.
  clean_dir: 30
```

As you can see, you can reference values from within `properties.yml` by using
following notation: `${path.to.property}`. There are also other tokens
available:

```
[user]          The ssh user we used to connect to the server.
[private-key]   The path to the private key that was used to connect to the
                server.
[time]          New releases are put in a folder with the current timestamp as
                folder name. This is that timestamp.
[app]           The name of the app that is being deployed.
```

### List of available events

Event arguments can be retrieved with `$event->getArgument($argumentName);`

#### digipolis:backup-remote

The handler for this event should return a task that creates a backup on a
host, based on options that are passed.

*Default handler*: [BackupRemoteHandler](src/EventHandler/DefaultHandler/BackupRemoteHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app of which we're going to create a backup.
  - options: Options for the backup. An array with keys:
    - files (bool): Whether or not to create a backup of the files.
    - data (bool): Whether or not to create a backup of the database.
  - fileBackupConfig: Configuration for the file backup. An array with keys:
    - exclude_from_backup: Files and/or directories to exclude from the backup.
    - file_backup_subdirs: The subdirectories of the files directory that need
      to be backed up.
  - timeouts: SSH timeouts for relevant tasks. An array with keys:
    - backup_files: Timeout in seconds for the files backup.
    - backup_database: Timeout in second for the database backup.

### digipolis:build-task

The handler for this event should return a task that creates a release archive
of the current codebase to upload to an environment.

*Default handler*: [BuildTaskHandler](src/EventHandler/DefaultHandler/BuildTaskHandler.php)<br/>
*Event arguments*:
  - archiveName: The name of the archive that should be created.

### digipolis:clean-dirs

The handler for this event should return a task that cleans the releases
directory by removing the older releases.

*Default handler*: [CleanDirsHandler](src/EventHandler/DefaultHandler/CleanDirsHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app of which we're going to clean the releases
    directory.

### digipolis:clear-cache

The handler for this event should return a task that clears the cache on the
remote host.

*Default handler*: [ClearCacheHandler](src/EventHandler/DefaultHandler/ClearCacheHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app of which we're going to clear the cache.

### digipolis:clear-remote-opcache

The handler for this event should return a task that clears the opcache on the
remote host.

*Default handler*: [ClearRemoteOpcacheHandler](src/EventHandler/DefaultHandler/ClearRemoteOpcacheHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app of which we're going to clear the opcache.
  - timeouts: SSH timeouts for relevant tasks. An array with keys:
    - clear_op_cache: Timeout in seconds for clearing the opcache.

### digipolis:compress-old-release

The handler for this event should return a task that compresses old releases on
the host for the given app.

*Default handler*: [CompressOldReleaseHandler](src/EventHandler/DefaultHandler/CompressOldReleaseHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app of which we're going to compress the old
    releases.
  - releaseToCompress: The path to the release directory that should be
    compressed.
  - timeouts: SSH timeouts for relevant tasks. An array with keys:
    - compress_old_release: Timeout in seconds for compressing the release.

### digipolis:current-project-root

The handler for this event should return the path to the current project root
for the given app on the given host. This means the actual path, not a task that
will return it when executed.

*Default handler*: [CurrentProjectRootHandler](src/EventHandler/DefaultHandler/CurrentProjectRootHandler.php)<br/>
*Event arguments*:
  - host: The host on which to get the project root.
  - user: The SSH user to connect to the host.
  - privateKeyFile: The path to the private key to use to connect to the host.
  - remoteSettings: The remote settings for the given host and app as parsed
    from `properties.yml`.

### digipolis:download-backup

The handler for this event should return a task that downloads a backup of an
app from a host.

*Default handler*: [DownloadBackupHandler](src/EventHandler/DefaultHandler/DownloadBackupHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app of which we're going to download a backup.
  - options: Options for the backup. An array with keys:
    - files (bool): Whether or not a backup of the files was created.
    - data (bool): Whether or not a backup of the database was created.

### digipolis:install

The handler for this event should return a task that executes the install script
on the host.

*Default handler*: [InstallHandler](src/EventHandler/DefaultHandler/InstallHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app we're going to install.
  - options: Options passed from the command to the install task.
  - force: Boolean indicating whether or not to force the install, even if there
    already is an installation.

### digipolis:is-site-installed

The handler for this event should return a boolean indicating whether or not
there already is an active installation of the app on the host. This means the
actual boolean, not a task that will return it when executed. This helps us to
determine whether the install or the update script should be ran when deploying
the app.

*Default handler*: [IsSiteInstalledHandler](src/EventHandler/DefaultHandler/IsSiteInstalledHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app we're checking.

### digipolis:get-local-settings

The handler for this event should return the settings for the local installation
of the app as parsed from `properties.yml`.

*Default handler*: [LocalSettingsHandler](src/EventHandler/DefaultHandler/LocalSettingsHandler.php)<br/>
*Event arguments*:
  - app: The name of the app.
  - timestamp: The current timestamp (sometimes used as token in paths).

#### digipolis:mirror-dir

The handler for this event should return a task that mirrors everything (files,
symlink, subdirectories, ...) from one directory to another.

*Default Handler*: [MirrorDirHandler](src/EventHandler/DefaultHandler/MirrorDirHandler.php)<br/>
*Event arguments*:
  - dir: The directory to mirror.
  - destination: The destination path to mirror the directory to.

### digipolis:post-symlink

The handler for this event should return a task that will be executed after
creating the symlinks (as parsed from `properties.yml`) on the remote host.

*Default Handler*: [PostSymlinkHandler](src/EventHandler/DefaultHandler/PostSymlinkHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - timeouts: SSH timeouts for relevant tasks. An array with keys:
    - post_symlink: Timeout in seconds for the post symlink tasks.

### digipolis:pre-local-sync-files

The handler for this event should return a task that should be executed before
syncing files from a remote installation to your local installation.

*Default Handler*: [PreLocalSyncFilesHandler](src/EventHandler/DefaultHandler/PreLocalSyncFilesHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - localSettings: the settings for the local installation of the app as parsed
    from `properties.yml`.

### digipolis:pre-restore-backup-remote

The handler for this event should return a task that should be executed before
restoring a backup on a host.

*Default Handler*: [PreRestoreBackupRemoteHandler](src/EventHandler/DefaultHandler/PreRestoreBackupRemoteHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - fileBackupConfig: Configuration for the file backup. An array with keys:
    - exclude_from_backup: Files and/or directories to exclude from the backup.
    - file_backup_subdirs: The subdirectories of the files directory that need
      to be backed up.
  - options: Options for the backup. An array with keys:
    - files (bool): Whether or not a backup of the files was created.
    - data (bool): Whether or not a backup of the database was created.
  - timeouts: SSH timeouts for relevant tasks. An array with keys:
    - pre_restore: Timeout in seconds for the pre restore task.

### digipolis:pre-symlink

The handler for this event should return a task that should be executed before
the symlinks on the remote host are created.

*Default Handler*: [PreSymlinkHandler](src/EventHandler/DefaultHandler/PreSymlinkHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - timeouts: SSH timeouts for relevant tasks. An array with keys:
    - pre_symlink: Timeout in seconds for the pre symlink task.

### digipolis:push-package

The handler for this event should return a task that pushes a release archive to
a host.

*Default Handler*: [PushPackageHandler](src/EventHandler/DefaultHandler/PushPackageHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - archiveName: The name of the archive that should be pushed.

### digipolis:realpath

The handler for this event should return the `realpath` of the given path. This
means the actual path, not a task that will return it when executed. The default
handler supports replacing `~` (tilde) with the user's homedir.

*Default handler*: [RealpathHandler](src/EventHandler/DefaultHandler/RealpathHandler.php)<br/>
*Event arguments*:
  - path: The path to get the real path for.

### digipolis:get-remote-settings

The handler for this event should return the settings for the remote
installation of the app as parsed from `properties.yml`. This means the actual
settings, not a task that will return it when executed.

*Default handler*: [RemoteSettingsHandler](src/EventHandler/DefaultHandler/RemoteSettingsHandler.php)<br/>
*Event arguments*:
  - servers: An array of servers (can be one, or multiple for loadbalanced
  setups) where the app resides.
  - user: The SSH user to connect to the servers.
  - privateKeyFile: The path to the private key to use to connect to the
    servers.
  - app: The name of the app.
  - timestamp: The current timestamp (sometimes used as token in paths).

### digipolis:remote-switch-previous

The handler for this event should return a task that will switch the `current`
symlink to the previous release (mostly used on rollback of a failed release).

*Default Handler*: [RemoteSwitchPreviousHandler](src/EventHandler/DefaultHandler/RemoteSwitchPreviousHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.

### digipolis:remote-symlink

The handler for this event should return a task that will create the symlinks as
defined in `properties.yml`.

*Default Handler*: [RemoteSymlinkPreviousHandler](src/EventHandler/DefaultHandler/RemoteSymlinkPreviousHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.

### digipolis:remove-backup-remote

The handler for this event should return a task that removes a backup from the
host.

*Default Handler*: [RemoveBackupRemoteHandler](src/EventHandler/DefaultHandler/RemoveBackupRemoteHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - options: Options for the backup. An array with keys:
    - files (bool): Whether or not a backup of the files was created.
    - data (bool): Whether or not a backup of the database was created.
  - timeouts: SSH timeouts for relevant tasks. An array with keys:
    - remove_backup: Timeout in seconds for the pre symlink task.

### digipolis:remove-failed-release

The handler for this event should return a task that removes a failed release
from the host.

*Default Handler*: [RemoveFailedReleaseHandler](src/EventHandler/DefaultHandler/RemoveFailedReleaseHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - releaseDir: The release directory to remove.

### digipolis:remove-local-backup

The handler for this event should return a task that removes a backup from your
local machine.

*Default Handler*: [RemoveLocalBackupHandler](src/EventHandler/DefaultHandler/RemoveLocalBackupHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - options: Options for the backup. An array with keys:
    - files (bool): Whether or not a backup of the files was created.
    - data (bool): Whether or not a backup of the database was created.
  - fileBackupConfig: Configuration for the file backup. An array with keys:
    - exclude_from_backup: Files and/or directories to exclude from the backup.
    - file_backup_subdirs: The subdirectories of the files directory that need
      to be backed up.

### digipolis:restore-backup-db-local

The handler for this event should return a task that restores a database backup
on your local machine.

*Default Handler*: [RestoreBackupDbLocalHandler](src/EventHandler/DefaultHandler/RestoreBackupDbLocalHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - localSettings: the settings for the local installation of the app as parsed
    from `properties.yml`.

### digipolis:restore-backup-files-local

The handler for this event should return a task that restores a files backup on
your local machine.

*Default Handler*: [RestoreBackupFilesLocalHandler](src/EventHandler/DefaultHandler/RestoreBackupFilesLocalHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - localSettings: the settings for the local installation of the app as parsed
    from `properties.yml`.

### digipolis:restore-backup-remote

The handler for this event should return a task that restores a backup on a
host.

*Default Handler*: [RestoreBackupRemoteHandler](src/EventHandler/DefaultHandler/RestoreBackupRemoteHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - options: Options for the backup. An array with keys:
    - files (bool): Whether or not to create a backup of the files.
    - data (bool): Whether or not to create a backup of the database.
  - fileBackupConfig: Configuration for the file backup. An array with keys:
    - exclude_from_backup: Files and/or directories to exclude from the backup.
    - file_backup_subdirs: The subdirectories of the files directory that need
      to be backed up.
  - timeouts: SSH timeouts for relevant tasks. An array with keys:
    - restore_files_backup: Timeout in seconds for the files backup.
    - restore_db_backup: Timeout in second for the database backup.

### digipolis:rsync-files-between-hosts

The handler for this event should return a task that rsyncs files between two
hosts.

*Default Handler*: [RsyncFilesBetweenHostsHandler](src/EventHandler/DefaultHandler/RsyncFilesBetweenHostsHandler.php)<br/>
*Event arguments*:
  - sourceRemoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object
    with data relevant to the source host and app.
  - destinationRemoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php)
    object with data relevant to the destination host and app.
  - fileBackupConfig: Configuration for the file backup. An array with keys:
    - exclude_from_backup: Files and/or directories to exclude from the backup.
    - file_backup_subdirs: The subdirectories of the files directory that need
      to be backed up.
  - timeouts: SSH timeouts for relevant tasks. An array with keys:
    - synctask_rsync: Timeout in seconds for the rsync.

### digipolis:rsync-files-to-local

The handler for this event should return a task that rsyncs files to your local
machine.

*Default Handler*: [RsyncFilesToLocalHandler](src/EventHandler/DefaultHandler/RsyncFilesToLocalHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app.
  - localSettings: the settings for the local installation of the app as parsed
    from `properties.yml`.
  - directory: The subdirectory under the `$remoteSettings['filesdir']` that
    should be synced.
  - - fileBackupConfig: Configuration for the file backup. An array with keys:
    - exclude_from_backup: Files and/or directories to exclude from the backup.
    - file_backup_subdirs: The subdirectories of the files directory that need
      to be backed up.

### digipolis:switch-previous

The handler for this event should return a task that will switch the `current`
symlink to the previous release (mostly used on rollback of a failed release).
The difference with the (digipolis:remote-switch-previous)[#digipolisremote-switch-previous]
event is that this will be executed directly on the host, and thus doesn't need
an ssh connection, while the (digipolis:remote-switch-previous)[#digipolisremote-switch-previous]
will be executed from your deployment server, or your local machine, and thus
will need an ssh connection to the host.

*Default Handler*: [SwitchPreviousHandler](src/EventHandler/DefaultHandler/SwitchPreviousHandler.php)<br/>
*Event arguments*:
  - releasesDir: The directory containing all your releases.
  - currentSymlink: The path to your `current` symlink.

### digipolis:timeout-setting

The handler for this event should return the the timeout setting of the given
type in seconds. This means the actual setting, not a task that will return it
when executed.

*Default handler*: [TimeoutSettingHandler](src/EventHandler/DefaultHandler/TimeoutSettingHandler.php)<br/>
*Event arguments*:
  - type: The type of timeout setting to get. See timeout event arguments for
    the other events.

### digipolis:update

The handler for this event should return a task that executes the update script
on the host.

*Default handler*: [UpdateHandler](src/EventHandler/DefaultHandler/UpdateHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app we're going to update.
  - options: Options passed from the command to the update task.
  - force: Boolean indicating whether or not to force the install, even if there
    already is an installation.

### digipolis:upload-backup

The handler for this event should return a task that uploads a backup of an
app to a host.

*Default handler*: [UploadBackupHandler](src/EventHandler/DefaultHandler/UploadBackupHandler.php)<br/>
*Event arguments*:
  - remoteConfig: The [RemoteConfig](src/Util/RemoteConfig.php) object with data
    relevant to the host and app of which we're going to download a backup.
  - options: Options for the backup. An array with keys:
    - files (bool): Whether or not a backup of the files was created.
    - data (bool): Whether or not a backup of the database was created.

# Robo Digipolis Helpers

[![Latest Stable Version](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/v/stable)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)
[![Latest Unstable Version](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/v/unstable)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)
[![Total Downloads](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/downloads)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)
[![License](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/license)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)

[![Build Status](https://travis-ci.org/digipolisgent/robo-digipolis-helpers.svg?branch=develop)](https://travis-ci.org/digipolisgent/robo-digipolis-helpers)
[![Maintainability](https://api.codeclimate.com/v1/badges/1c4c5693cb7945f5e5e9/maintainability)](https://codeclimate.com/github/digipolisgent/robo-digipolis-helpers/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/1c4c5693cb7945f5e5e9/test_coverage)](https://codeclimate.com/github/digipolisgent/robo-digipolis-helpers/test_coverage)
[![PHP 7 ready](https://php7ready.timesplinter.ch/digipolisgent/robo-digipolis-helpers/develop/badge.svg)](https://travis-ci.org/digipolisgent/robo-digipolis-helpers)


Used by digipolis, abstract robo file to help with the deploy flow.


By default, we assume a [capistrano-like directory structure](http://capistranorb.com/documentation/getting-started/structure/):
```
├── current -> releases/20150120114500/
├── releases
│   ├── 20150080072500
│   ├── 20150090083000
│   ├── 20150100093500
│   ├── 20150110104000
│   └── 20150120114500
```

## Example implementation

### RoboFile.php

```php
<?php

use DigipolisGent\Robo\Helpers\AbstractRoboFile;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

class RoboFile extends AbstractRoboFile
{
    public function digipolisValidateCode()
    {
        $collection = $this->collectionBuilder();
        $collection->addTask($this->taskExec('phpcs --standard=PSR2 ./src'));
        return $collection;
    }

    /**
     * Detects whether this site is installed or not. This method is used to
     * determine whether we should run `updateTask` (if this returns `true`) or
     * `installTask` (if this returns `false`).
     */
    protected function isSiteInstalled($worker, AbstractAuth $auth, $remote)
    {
        $currentProjectRoot = $this->getCurrentProjectRoot($worker, $auth, $remote);
        $migrateStatus = '';
        return $this->taskSsh($worker, $auth)
            ->remoteDirectory($currentProjectRoot, true)
            ->exec('ls -al | grep index.php')
            ->run()
            ->wasSuccessful();
    }

    protected function updateTask($worker, AbstractAuth $auth, $remote)
    {
        $currentProjectRoot = $remote['rootdir'];
        return $this->taskSsh($server, $auth)
            ->remoteDirectory($currentProjectRoot, true)
            ->exec('./update.sh');
    }

    protected function installTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $extra = [],
        $force = false
    ) {
        $currentProjectRoot = $remote['rootdir'];
        return $this->taskSsh($server, $auth)
            ->remoteDirectory($currentProjectRoot, true)
            ->exec('./install.sh');
    }

    /**
     * Build a my site and push it to the server(s).
     *
     * @param array $arguments
     *   Variable amount of arguments. The last argument is the path to the
     *   the private key file (ssh), the penultimate is the ssh user. All
     *   arguments before that are server IP's to deploy to.
     * @param array $opts
     *   The options for this command.
     *
     * @option option1 Description of the first option.
     * @option option2 Description of the second option.
     *
     * @usage --option1=first --option2=2 192.168.1.2 sshuser /home/myuser/.ssh/id_rsa
     */
    public function myDeployCommand(
        array $arguments,
        $opts = ['option1' => 'one', 'option2' => 'two']
    ) {
        return $this->deployTask($arguments, $opts);
    }
}
```

If you place this in `RoboFile.php` in your project root, you'll be able to run
`vendor/bin/robo my:deploy-command --option1=1 --option2=2 192.168.1.2 sshuser /home/myuser/.ssh/id_rsa`
to release your website. The script will automatically detect whether it should
update your site or do a fresh install, based on your implementation of
`isSiteInstalled`. Note that this command can only run after the `composer install`
command completed successfully (without any errors).

### properties.yml

You need to provide a `properties.yml` file in your project root as well, so
the script knows what symlinks to create, where to put backups, ...

Below is an example of some sensible defaults:

```YAML
remote:
  # The application directory where your capistrano folder structure resides.
  appdir: '/home/[user]'
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
    # Here you can specify your overrides. The keys are the values to match
    # against the value of the specified environment variable, using the
    # specified matcher. Since this example uses the regex matcher, we specified
    # a PCRE regular expression. The values can be anything under the `remote`
    # key in this specification, and will override said setting for the matching
    # environment. Only the first match is used (top to bottom).
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
  pre_restore_remove_files: 300
  # See ${remote.cleandir_limit}. This is the timeout for that operation.
  clean_dir: 30
```

As you can see, you can reference values from within `properties.yml` by using
following notation: `${path.to.property}`. There are also other tokens
available:

```
[user]    The ssh user we used to connect to the server.
[time]    New releases are put in a folder with the current timestamp as folder
          name. This is that timestamp.
```

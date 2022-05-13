<?php

namespace DigipolisGent\Robo\Helpers\Traits;

trait Tasks
{
    use RemoteCleanDirsTrait;
    use RemoteDatabaseBackupTrait;
    use RemoteFilesBackupTrait;
    use RemoteSwitchPreviousTrait;
    use RemoteRemoveReleaseTrait;
    use SwitchPreviousTrait;
}

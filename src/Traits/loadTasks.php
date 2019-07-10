<?php

namespace DigipolisGent\Robo\Helpers\Traits;

trait loadTasks
{
    use RemoteCleanDirsTrait;
    use RemoteDatabaseBackupTrait;
    use RemoteFilesBackupTrait;
    use RemoteSwitchPreviousTrait;
    use SwitchPreviousTrait;
}

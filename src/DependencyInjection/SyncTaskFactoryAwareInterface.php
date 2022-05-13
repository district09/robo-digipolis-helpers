<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Sync;

interface SyncTaskFactoryAwareInterface
{
    public function setSyncTaskFactory(Sync $syncTaskFactory);
}

<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection\Traits;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Sync;

trait SyncTaskFactoryAware
{
    protected Sync $syncTaskFactory;

    public function setSyncTaskFactory(Sync $syncTaskFactory)
    {
        $this->syncTaskFactory = $syncTaskFactory;
    }
}

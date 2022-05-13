<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection\Traits;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Cache;

trait CacheTaskFactoryAware
{
    protected Cache $cacheTaskFactory;

    public function setCacheTaskFactory(Cache $cacheTaskFactory)
    {
        $this->cacheTaskFactory = $cacheTaskFactory;
    }
}

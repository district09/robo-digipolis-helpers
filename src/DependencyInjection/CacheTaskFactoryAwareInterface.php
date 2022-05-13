<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Cache;

interface CacheTaskFactoryAwareInterface
{
    public function setCacheTaskFactory(Cache $cacheTaskFactory);
}

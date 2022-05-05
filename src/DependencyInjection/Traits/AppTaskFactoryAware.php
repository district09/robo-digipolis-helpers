<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection\Traits;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\AbstractApp;

trait AppTaskFactoryAware
{
    protected AbstractApp $appTaskFactory;

    public function setAppTaskFactory(AbstractApp $appTaskFactory)
    {
        $this->appTaskFactory = $appTaskFactory;
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection\Traits;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Deploy;

trait DeployTaskFactoryAware
{
    protected Deploy $deployTaskFactory;

    public function setDeployTaskFactory(Deploy $deployTaskFactory)
    {
        $this->deployTaskFactory = $deployTaskFactory;
    }
}

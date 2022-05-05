<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Deploy;

interface DeployTaskFactoryAwareInterface
{
    public function setDeployTaskFactory(Deploy $deployTaskFactory);
}

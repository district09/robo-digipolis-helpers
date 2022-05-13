<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Build;

interface BuildTaskFactoryAwareInterface
{
    public function setBuildTaskFactory(Build $buildTaskFactory);
}

<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection\Traits;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Build;

trait BuildTaskFactoryAware
{
    protected Build $buildTaskFactory;

    public function setBuildTaskFactory(Build $buildTaskFactory)
    {
        $this->buildTaskFactory = $buildTaskFactory;
    }
}

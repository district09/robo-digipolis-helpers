<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\AbstractApp;

interface AppTaskFactoryAwareInterface
{
    public function setAppTaskFactory(AbstractApp $appTaskFactory);
}

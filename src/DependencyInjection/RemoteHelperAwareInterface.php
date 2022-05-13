<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection;

use DigipolisGent\Robo\Helpers\Util\RemoteHelper;

interface RemoteHelperAwareInterface
{
    public function setRemoteHelper(RemoteHelper $remoteHelper);
}

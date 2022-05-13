<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection\Traits;

use DigipolisGent\Robo\Helpers\Util\RemoteHelper;

trait RemoteHelperAware
{
    protected RemoteHelper $remoteHelper;

    public function setRemoteHelper(RemoteHelper $remoteHelper)
    {
        $this->remoteHelper = $remoteHelper;
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection\Traits;

use DigipolisGent\Robo\Helpers\Util\PropertiesHelper;

trait PropertiesHelperAware
{
    protected PropertiesHelper $propertiesHelper;

    public function setPropertiesHelper(PropertiesHelper $propertiesHelper)
    {
        $this->propertiesHelper = $propertiesHelper;
    }
}

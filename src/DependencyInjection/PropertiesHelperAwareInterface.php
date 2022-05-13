<?php

namespace DigipolisGent\Robo\Helpers\DependencyInjection;

use DigipolisGent\Robo\Helpers\Util\PropertiesHelper;

interface PropertiesHelperAwareInterface
{
    public function setPropertiesHelper(PropertiesHelper $propertiesHelper);
}

<?php

namespace DigipolisGent\Robo\Helpers\Util;

use Consolidation\Config\ConfigAwareTrait;
use DigipolisGent\Robo\Task\General\Common\DigipolisPropertiesAware;
use DigipolisGent\Robo\Task\General\Common\DigipolisPropertiesAwareInterface;
use DigipolisGent\Robo\Task\General\Tasks;
use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\TaskAccessor;

class PropertiesHelper implements DigipolisPropertiesAwareInterface, BuilderAwareInterface, ConfigAwareInterface
{

    use DigipolisPropertiesAware;
    use TaskAccessor;
    use Tasks;
    use ConfigAwareTrait;

    public static function create(DefinitionContainerInterface $container)
    {
        $object = new static();
        $object->setBuilder(CollectionBuilder::create($container, $object));

        return $object;
    }

}

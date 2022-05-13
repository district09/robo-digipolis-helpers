<?php

namespace DigipolisGent\Robo\Helpers\Util\TaskFactory;

use DigipolisGent\Robo\Helpers\DependencyInjection\PropertiesHelperAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\RemoteHelperAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\PropertiesHelperAware;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\RemoteHelperAware;
use DigipolisGent\Robo\Helpers\Util\PropertiesHelper;
use DigipolisGent\Robo\Helpers\Util\RemoteHelper;
use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;

class Build implements BuilderAwareInterface, PropertiesHelperAwareInterface, RemoteHelperAwareInterface
{
    use TaskAccessor;
    use PropertiesHelperAware;
    use RemoteHelperAware;
    use \DigipolisGent\Robo\Task\Package\Tasks;

    public function __construct(PropertiesHelper $propertiesHelper, RemoteHelper $remoteHelper)
    {
        $this->setPropertiesHelper($propertiesHelper);
        $this->setRemoteHelper($remoteHelper);
    }

    public static function create(DefinitionContainerInterface $container)
    {
        $object = new static(
            $container->get(PropertiesHelper::class),
            $container->get(RemoteHelper::class)
        );
        $object->setBuilder(CollectionBuilder::create($container, $object));

        return $object;
    }

    /**
     * Build a site and package it.
     *
     * @param string $archivename
     *   Name of the archive to create.
     *
     * @return \Robo\Contract\TaskInterface
     *   The deploy task.
     */
    public function buildTask($archivename = null)
    {
        $this->propertiesHelper->readProperties();
        $archive = is_null($archivename) ? $this->remoteHelper->getTime() . '.tar.gz' : $archivename;
        $collection = $this->collectionBuilder();
        $collection
            ->taskPackageProject($archive);
        return $collection;
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\Util\TaskFactory;

use Consolidation\Config\ConfigAwareInterface;
use Consolidation\Config\ConfigAwareTrait;
use Consolidation\Config\ConfigInterface;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;

abstract class AbstractApp implements BuilderAwareInterface, ConfigAwareInterface
{
    use TaskAccessor;
    use ConfigAwareTrait;

    public function __construct(ConfigInterface $config)
    {
        $this->setConfig($config);
    }

    public static function create(DefinitionContainerInterface $container)
    {
        $object = new static($container->get('config'));
        $object->setBuilder(CollectionBuilder::create($container, $object));

        return $object;
    }

    /**
     * Install the site in the current folder.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     * @param bool $force
     *   Whether or not to force the install even when the site is present.
     *
     * @return \Robo\Contract\TaskInterface
     *   The install task.
     */
    abstract public function installTask($worker, AbstractAuth $auth, $remote, $extra = [], $force = false);

    /**
     * Executes database updates of the site in the current folder.
     *
     * Executes database updates of the site in the current folder. Sets
     * the site in maintenance mode before the update and takes in out of
     * maintenance mode after.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The update task.
     */
    abstract public function updateTask($worker, AbstractAuth $auth, $remote);

    /**
     * Check if a site is already installed
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return bool
     *   Whether or not the site is installed.
     */
    abstract public function isSiteInstalled($worker, AbstractAuth $auth, $remote);

    /**
     * Clear cache of the site.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return bool|\Robo\Contract\TaskInterface
     *   The clear cache task or false if no clear cache task exists.
     */
    abstract public function clearCacheTask($worker, $auth, $remote);
}

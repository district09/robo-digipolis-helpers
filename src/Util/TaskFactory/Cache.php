<?php

namespace DigipolisGent\Robo\Helpers\Util\TaskFactory;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\DependencyInjection\AppTaskFactoryAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\RemoteHelperAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\AppTaskFactoryAware;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\RemoteHelperAware;
use DigipolisGent\Robo\Helpers\Util\RemoteHelper;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;

class Cache implements BuilderAwareInterface, AppTaskFactoryAwareInterface, RemoteHelperAwareInterface
{
    use TaskAccessor;
    use \DigipolisGent\Robo\Task\Deploy\Tasks;
    use AppTaskFactoryAware;
    use RemoteHelperAware;

    public function __construct(AbstractApp $appTaskFactory, RemoteHelper $remoteHelper)
    {
        $this->setAppTaskFactory($appTaskFactory);
        $this->setRemoteHelper($remoteHelper);
    }

    public static function create(DefinitionContainerInterface $container)
    {
        $object = new static(
            $container->get(AbstractApp::class),
            $container->get(RemoteHelper::class)
        );
        $object->setBuilder(CollectionBuilder::create($container, $object));

        return $object;
    }

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
    public function clearCacheTask($worker, $auth, $remote)
    {
        return $this->appTaskFactory->clearCacheTask($worker, $auth, $remote);
    }

    /**
     * Clear OPcache on the server.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The clear OPcache task.
     */
    public function clearOpCacheTask($worker, AbstractAuth $auth, $remote)
    {
        $clearOpcache = CommandBuilder::create('vendor/bin/robo digipolis:clear-op-cache')->addArgument($remote['opcache']['env']);
        if (isset($remote['opcache']['host'])) {
            $clearOpcache->addOption('host', $remote['opcache']['host']);
        }
        return $this->taskSsh($worker, $auth)
            ->remoteDirectory($remote['rootdir'], true)
            ->timeout($this->remoteHelper->getTimeoutSetting('clear_op_cache'))
            ->exec((string) $clearOpcache);
    }
}

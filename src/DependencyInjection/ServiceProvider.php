<?php


namespace DigipolisGent\Robo\Helpers\DependencyInjection;

use DigipolisGent\Robo\Helpers\Util\PropertiesHelper;
use DigipolisGent\Robo\Helpers\Util\RemoteHelper;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Backup;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Build;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Cache;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Deploy;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Sync;
use League\Container\ServiceProvider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{

    public function provides(string $id): bool {
        $services = [
            'digipolis.time',
            RemoteHelper::class,
            PropertiesHelper::class,
            Backup::class,
            Build::class,
            Cache::class,
            Deploy::class,
            Sync::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void {
        $container = $this->getContainer();
        $container->addShared('digipolis.time', time());
        $container->addShared(PropertiesHelper::class, [PropertiesHelper::class, 'create'])
            ->addArgument($container);
        $container->addShared(RemoteHelper::class, [RemoteHelper::class, 'create'])
            ->addArgument($container);
        $container->addShared(Backup::class, [Backup::class, 'create'])
            ->addArgument($container);
        $container->addShared(Build::class, [Build::class, 'create'])
            ->addArgument($container);
        $container->addShared(Cache::class, [Cache::class, 'create'])
            ->addArgument($container);
        $container->addShared(Deploy::class, [Deploy::class, 'create'])
            ->addArgument($container);
        $container->addShared(Sync::class, [Sync::class, 'create'])
            ->addArgument($container);
    }
}

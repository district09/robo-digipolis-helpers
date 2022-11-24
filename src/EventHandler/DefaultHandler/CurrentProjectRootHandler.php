<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class CurrentProjectRootHandler extends AbstractTaskEventHandler implements ConfigAwareInterface
{

    use \Consolidation\Config\ConfigAwareTrait;
    use \DigipolisGent\Robo\Task\General\Common\DigipolisPropertiesAware;
    use \DigipolisGent\Robo\Task\General\Tasks;
    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    protected $projectRoots = [];

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $host = $event->getArgument('host');
        $user = $event->getArgument('user');
        $privateKeyFile = $event->getArgument('privateKeyFile');
        $remoteSettings = $event->getArgument('remoteSettings');
        $key = $host . ':' . $user . ':' . $privateKeyFile . ':' . $remoteSettings['releasesdir'];

        $auth = new KeyFile($user, $privateKeyFile);
        if (!array_key_exists($key, $this->projectRoots)) {
            $fullOutput = '';
            $this->taskSsh($host, $auth)
                ->remoteDirectory($remoteSettings['releasesdir'], true)
                ->exec(
                    (string) CommandBuilder::create('ls')
                        ->addFlag('1')
                        ->pipeOutputTo(
                            CommandBuilder::create('sort')
                                ->addFlag('r')
                                ->pipeOutputTo(
                                    CommandBuilder::create('head')
                                        ->addFlag('1')
                                )
                        ),
                    function ($output) use (&$fullOutput) {
                        $fullOutput .= $output;
                    }
                )
                ->run();
            $this->projectRoots[$key] = $remoteSettings['releasesdir'] . '/' . substr($fullOutput, 0, (strpos($fullOutput, "\n") ?: strlen($fullOutput)));
        }

        return $this->projectRoots[$key];
    }
}

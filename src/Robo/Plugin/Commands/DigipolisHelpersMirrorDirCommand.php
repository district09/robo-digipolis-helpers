<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use DigipolisGent\Robo\Helpers\Traits\EventDispatcher;
use Robo\Tasks;

class DigipolisHelpersMirrorDirCommand extends Tasks implements CustomEventAwareInterface
{
    use CustomEventAwareTrait;
    use EventDispatcher;

    /**
     * Mirror a directory.
     *
     * @param string $dir
     *   Path of the directory to mirror.
     * @param string $destination
     *   Path of the directory where $dir should be mirrored.
     *
     * @return \Robo\Contract\TaskInterface
     *   The mirror dir task.
     *
     * @command digipolis:mirror-dir
     */
    public function digipolisMirrorDir($dir, $destination)
    {
        return $this->handleTaskEvent(
            'digipolis:mirror-dir',
            ['dir' => $dir, 'destination' => $destination]
        );
    }
}

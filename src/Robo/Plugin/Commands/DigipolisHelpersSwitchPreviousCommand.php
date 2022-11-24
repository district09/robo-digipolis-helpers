<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use DigipolisGent\Robo\Helpers\Traits\EventDispatcher;
use Robo\Tasks;

class DigipolisHelpersSwitchPreviousCommand extends Tasks implements CustomEventAwareInterface
{
    use CustomEventAwareTrait;
    use EventDispatcher;

    /**
     * Switch the current release symlink to the previous release.
     *
     * @param string $releasesDir
     *   Path to the folder containing all releases.
     * @param string $currentSymlink
     *   Path to the current release symlink.
     *
     * @command digipolis:switch-previous
     */
    public function digipolisSwitchPrevious($releasesDir, $currentSymlink)
    {
        return $this->handleTaskEvent(
            'digipolis:switch-previous',
            ['releasesDir' => $releasesDir, 'currentSymlink' => $currentSymlink]
        );
    }
}

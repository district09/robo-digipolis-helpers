<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\Robo\Helpers\Robo\Plugins\Tasks\SwitchPrevious;

trait SwitchPreviousTrait
{

    /**
     * Creates a new SwitchPrevious task.
     *
     * @param string $releasesDir
     *   The releases directory.
     * @param string $currentSymlink
     *   The current release directory.
     *
     * @return SwitchPrevious
     */
    protected function taskSwitchPrevious($releasesDir, $currentSymlink)
    {
        return $this->task(SwitchPrevious::class, $releasesDir, $currentSymlink);
    }
}

<?php

namespace DigipolisGent\Robo\Helpers\Traits;

trait DigipolisSwitchPreviousCommandTrait
{
    /**
     * @see \DigipolisGent\Robo\Helpers\Traits\TraitDependencyCheckerTrait
     */
    protected function getDigipolisSwitchPreviousCommandTraitDependencies()
    {
        return [SwitchPreviousTrait::class];
    }

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
        return $this->taskSwitchPrevious($releasesDir, $currentSymlink);
    }
}

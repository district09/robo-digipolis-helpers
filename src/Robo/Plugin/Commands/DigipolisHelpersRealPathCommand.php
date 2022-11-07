<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use DigipolisGent\Robo\Helpers\Traits\EventDispatcher;
use Robo\Tasks;

class DigipolisHelpersRealPathCommand extends Tasks implements CustomEventAwareInterface
{
    use CustomEventAwareTrait;
    use EventDispatcher;

    /**
     * Polyfill for realpath.
     *
     * @param string $path
     *
     * @return string
     *
     * @command digipolis:realpath
     */
    public function digipolisRealpath($path)
    {
        $results = $this->handleEvent(
            'digipolis:realpath',
            ['path' => $path]
        );

        return reset($results);
    }
}

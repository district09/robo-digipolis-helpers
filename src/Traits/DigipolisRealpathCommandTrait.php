<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\Robo\Helpers\Util\Path;

trait DigipolisRealpathCommandTrait
{
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
        return Path::realpath($path);
    }
}

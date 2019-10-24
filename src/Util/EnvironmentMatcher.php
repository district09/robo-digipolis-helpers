<?php

namespace DigipolisGent\Robo\Helpers\Util;

/**
 * Class EnvironmentMatcher.
 */
class EnvironmentMatcher
{
    public static function regexMatch($environmentMatch, $envVarValue)
    {
        return preg_match('/' . $environmentMatch . '/', $envVarValue) === 1;
    }

    public static function literalMatch($environmentMatch, $envVarValue)
    {
        return $environmentMatch === $envVarValue;
    }
}

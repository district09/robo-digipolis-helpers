<?php

namespace DigipolisGent\Robo\Helpers\Util;

class Path {

    /**
     * PHP's realpath can't handle tilde (~), so we have to write a wrapper
     * for it.
     */
    public static function realpath($path)
    {
        $realpath = $path;
        if (strpos($realpath, '~') === 0 && ($homedir = static::getUserHomeDir())) {
            $realpath = $homedir . substr($realpath, 1);
        }
        $realpath = realpath($realpath);

        if ($realpath === false) {
            throw new \Exception(sprintf('Could not determine real path of %s.', $path));
        }

        return $realpath;
    }

    /**
     * Get the home directory for the current user.
     */
    public static function getUserHomeDir()
    {
        // getenv('HOME') isn't set on Windows.
        $home = getenv('HOME');
        if (!empty($home)) {
            // home should never end with a trailing slash.
            return rtrim($home, '/');
        }
        if (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash.
            // Make sure that doesn't happen.
            return rtrim($home, '\\/');
        }

        throw new \Exception('Could not determine the current user\'s home directory.');
    }
}

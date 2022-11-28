<?php

namespace DigipolisGent\Robo\Helpers\Util;

class TimeHelper
{

    protected static $instance;

    /**
     * The current timestamp.
     *
     * @var int
     */
    protected $time;

    /**
     * Protected constructor for singleton pattern.
     */
    protected function __construct()
    {
        $this->time = time();
    }

    /**
     * Get the singleton instance.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Get the timestamp.
     *
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }
}

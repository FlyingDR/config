<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Helper class to log requests
 */
class CallbackLog
{
    /**
     * Log of calls to callback
     *
     * @var array
     */
    private $log = [];

    /**
     * Add callback log entry
     *
     * @param string $method
     * @param array $args
     * @return void
     */
    public function add($method, array $args)
    {
        array_unshift($args, $method);
        $this->log[] = $args;
    }

    /**
     * Get callback log
     *
     * @return array
     */
    public function get()
    {
        return $this->log;
    }
}

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
    protected $_log = array();

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
        $this->_log[] = $args;
    }

    /**
     * Get callback log
     *
     * @return array
     */
    public function get()
    {
        return $this->_log;
    }

}

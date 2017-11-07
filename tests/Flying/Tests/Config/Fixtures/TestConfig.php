<?php

namespace Flying\Tests\Config\Fixtures;

use Flying\Config\AbstractConfig;

/**
 * Test class with ability to log calls to callback methods
 */
class TestConfig extends AbstractConfig implements CallbackTrackingInterface
{
    /**
     * Available callback loggers
     *
     * @var array
     */
    private $cbLogs = [];

    /**
     * Set logger for defined method
     *
     * @param string $method Method name
     * @param CallbackLog $logger
     * @return void
     */
    public function setCallbackLogger($method, CallbackLog $logger)
    {
        $this->cbLogs[$method] = $logger;
    }

    /**
     * Log call to callback
     *
     * @param string $method Method name
     * @param array $args    Method call arguments
     * @return void
     */
    protected function logCallbackCall($method, array $args)
    {
        if (array_key_exists($method, $this->cbLogs)) {
            /** @var $logger CallbackLog */
            $logger = $this->cbLogs[$method];
            $logger->add($method, $args);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function onConfigChange($name, $value)
    {
        $this->logCallbackCall(__FUNCTION__, func_get_args());
        parent::onConfigChange($name, $value);
    }
}

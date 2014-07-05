<?php

namespace Flying\Tests\Config\Fixtures;

use Flying\Config\ConfigurableInterface;

/**
 * Interface for configurable classes that can be used to test callback methods
 */
interface CallbackTrackingInterface extends ConfigurableInterface
{
    /**
     * Set logger for defined method
     *
     * @param string $method Method name
     * @param CallbackLog $logger
     * @return void
     */
    public function setCallbackLogger($method, CallbackLog $logger);

}

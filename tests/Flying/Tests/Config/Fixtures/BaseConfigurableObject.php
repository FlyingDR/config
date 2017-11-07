<?php

namespace Flying\Tests\Config\Fixtures;

use Flying\Config\ConfigurableInterface;
use Flying\Config\ObjectConfig;

/**
 * Base object that uses configurable functionality
 * from standalone object configuration
 */
abstract class BaseConfigurableObject implements ConfigurableInterface, CallbackTrackingInterface
{
    /**
     * Object configuration
     *
     * @var ObjectConfig
     */
    private $config;
    /**
     * Available callback loggers
     *
     * @var array
     */
    private $cbLogs = [];

    public function __construct()
    {
        $this->config = new ObjectConfig($this, $this->getConfigOptions(), $this->getConfigCallbacks());
    }

    /**
     * Get configuration options for test configuration object
     *
     * @return array
     */
    abstract protected function getConfigOptions();

    /**
     * Get callbacks for test configuration object
     *
     * @return array
     */
    abstract protected function getConfigCallbacks();

    /**
     * Check if configuration option with given name is available in object configuration
     *
     * @param string $name Configuration option name
     * @return boolean
     */
    public function isConfigExists($name)
    {
        return $this->config->isConfigExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig($config = null, $export = false)
    {
        return $this->config->getConfig($config, $export);
    }

    /**
     * Set configuration options for object
     *
     * @param array|string $config          Configuration options to set
     * @param mixed $value                  If first parameter is passed as string then it will be treated as
     *                                      configuration option name and $value as its value
     * @return void
     */
    public function setConfig($config, $value = null)
    {
        $this->config->setConfig($config, $value);
    }

    /**
     * Apply given modifications to given object configuration set and return resulted configuration
     *
     * @param array $config                 Object configuration options set
     * @param array|string $modification    Configuration modifications to apply to given object configuration
     * @param mixed $value                  OPTIONAL If $modification is passed as string - it is treated
     *                                      as single option name to modify and $value will be treated as new
     *                                      option value in this case. Ignored otherwise.
     * @return array
     */
    public function modifyConfig(array $config, $modification, $value = null)
    {
        return $this->config->modifyConfig($config, $modification, $value);
    }

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
}

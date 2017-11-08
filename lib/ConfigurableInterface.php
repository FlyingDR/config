<?php

namespace Flying\Config;

/**
 * Interface for configurable objects
 */
interface ConfigurableInterface
{
    /**
     * Name of array item within configuration options array
     * that defines ID of base class for configuration options set
     */
    const CLASS_ID_KEY = '__config__';

    /**
     * Check if configuration option with given name is available in object configuration
     *
     * @param string $name Configuration option name
     * @return boolean
     */
    public function isConfigExists($name);

    /**
     * Get object's configuration or configuration option with given name
     * If argument is passed as string - value of configuration option with this name will be returned
     * If argument is some kind of configuration options set - it will be merged with current object's configuration and returned
     * If no argument is passed - current object's configuration will be returned
     *
     * @param string|array|null $config     OPTIONAL Option name to get or configuration options
     *                                      to override default object's configuration.
     * @param boolean $export               OPTIONAL TRUE to skip adding CLASS_ID_KEY entry into resulted configuration array
         * @return mixed
     */
    public function getConfig($config = null, $export = false);

    /**
     * Set configuration options for object
     *
     * @param array|\ArrayAccess|\Iterator|\stdClass|string $config     Configuration options to set
     * @param mixed $value                  If first parameter is passed as string then it will be treated as
     *                                      configuration option name and $value as its value
     * @return void
     */
    public function setConfig($config, $value = null);

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
    public function modifyConfig(array $config, $modification, $value = null);
}

<?php

namespace Flying\Tests\Config\Object;

use Flying\Config\ConfigurableInterface;
use Flying\Config\ObjectConfig;

/**
 * Object that uses configurable functionality
 * from standalone object configuration
 */
class ConfigurableObject implements ConfigurableInterface
{
    /**
     * Object configuration
     * @var ObjectConfig
     */
    protected $_config;

    public function __construct()
    {
        $this->_config = new ObjectConfig($this, array(
            'string_option'  => 'some value',
            'boolean_option' => true,
            'int_option'     => 42,
            'rejected'       => 'abc',
            'exception'      => null,
        ), array(
            'validateConfig' => function ($name, &$value) {
                switch ($name) {
                    case 'string_option':
                        $value = trim($value);
                        break;
                    case 'boolean_option':
                        $value = (boolean)$value;
                        break;
                    case 'int_option':
                        $value = (int)$value;
                        break;
                    case 'rejected':
                        return false;
                        break;
                    case 'exception':
                        throw new \Exception('Test exception on checking config option');
                        break;
                }
                return true;
            },
        ));
    }

    /**
     * Check if configuration option with given name is available in object configuration
     *
     * @param string $name      Configuration option name
     * @return boolean
     */
    public function isConfigExists($name)
    {
        return ($this->_config->isConfigExists($name));
    }

    /**
     * Get object's configuration or configuration option with given name
     * If argument is passed as string - value of configuration option with this name will be returned
     * If argument is some kind of configuration options set - it will be merged with current object's configuration and returned
     * If no argument is passed - current object's configuration will be returned
     *
     * @param string|array|null $config     OPTIONAL Option name to get or configuration options
     *                                      to override default object's configuration.
     * @return mixed
     */
    public function getConfig($config = null)
    {
        return ($this->_config->getConfig($config));
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
        $this->_config->setConfig($config, $value);
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
    public function modifyConfig($config, $modification, $value = null)
    {
        return ($this->_config->modifyConfig($config, $modification, $value));
    }

}

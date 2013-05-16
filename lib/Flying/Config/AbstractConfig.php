<?php

namespace Flying\Config;

/**
 * Base implementation of configurable class
 */
abstract class AbstractConfig implements ConfigurableInterface
{
    /**
     * Configuration options
     * @var array
     */
    private $_config = null;
    /**
     * TRUE if configuration options bootstrap is being performed, FALSE otherwise
     * @var boolean
     */
    private $_configInBootstrap = false;
    /**
     * Mapping table between class name and its configuration options set
     * @var array
     */
    private static $_configClassesMap = array();

    /**
     * Check if configuration option with given name is available in object configuration
     *
     * @param string $name      Configuration option name
     * @return boolean
     */
    public final function isConfigExists($name)
    {
        if (!is_array($this->_config)) {
            $this->bootstrapConfig();
        }
        if ((is_string($name)) && ($name !== self::CLASS_ID_KEY)) {
            return (array_key_exists($name, $this->_config));
        }
        return (false);
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
        if (!is_array($this->_config)) {
            $this->bootstrapConfig();
        }
        if ($config === null) {
            $config = $this->_config;
            $config[self::CLASS_ID_KEY] = $this->getConfigClassId();
            return ($config);
        } elseif (is_string($config)) {
            // This is request for configuration option value
            if (array_key_exists($config, $this->_config)) {
                return ($this->_config[$config]);
            } else {
                return (null);
            }
        } elseif ((is_array($config)) &&
            (array_key_exists(self::CLASS_ID_KEY, $config)) && // This is repetitive call to getConfig()
            ($config[self::CLASS_ID_KEY] == $this->getConfigClassId())
        ) // Only classes with same configuration class Id can share configurations
        {
            return ($config);
        }
        // This is request for configuration (with possible merging)
        $config = $this->configToArray($config);
        if (!is_array($config)) {
            $config = array();
        }
        $result = $this->_config;
        $result[self::CLASS_ID_KEY] = $this->getConfigClassId();
        foreach ($config as $name => $value) {
            if ((!array_key_exists($name, $result)) || ($name == self::CLASS_ID_KEY)) {
                continue;
            }
            if ($this->validateConfig($name, $value)) {
                $result[$name] = $value;
            }
        }
        return ($result);
    }

    /**
     * Set configuration options for object
     *
     * @param array|string $config          Configuration options to set
     * @param mixed $value                  If first parameter is passed as string then it will be treated as
     *                                      configuration option name and $value as its value
     * @return void
     */
    public final function setConfig($config, $value = null)
    {
        if (!is_array($this->_config)) {
            $this->bootstrapConfig();
        }
        $config = $this->configToArray($config, $value, true);
        if ((!is_array($config)) || (!sizeof($config))) {
            return;
        }
        foreach ($config as $key => $value) {
            if (!array_key_exists($key, $this->_config)) {
                continue;
            }
            if (!$this->validateConfig($key, $value)) {
                continue;
            }
            $this->_config[$key] = $value;
            $this->onConfigChange($key, $value, false);
        }
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
        // Call getConfig() for given configuration options, but only if it is necessary
        // Without this check it is possible to get infinite recursion loop in a case
        // if getConfig() is overridden and calls modifyConfig() by itself
        if ((!is_array($config)) ||
            (!array_key_exists(self::CLASS_ID_KEY, $config)) ||
            ($config[self::CLASS_ID_KEY] != $this->getConfigClassId())
        ) {
            $config = $this->getConfig($config);
        }
        $modification = $this->configToArray($modification, $value, true);
        if ((!is_array($modification)) || (!sizeof($modification))) {
            return ($config);
        }
        foreach ($modification as $name => $value) {
            if ($name == self::CLASS_ID_KEY) {
                continue;
            }
            if ($this->validateConfig($name, $value)) {
                $config[$name] = $value;
            }
        }
        return ($config);
    }

    /**
     * Initialize list of configuration options
     *
     * @return void
     */
    protected function initConfig()
    {
        // This method is mean to be overridden to provide configuration options set.
        // To allow inheritance of configuration options sets across several levels
        // of inherited classes - this method in nested classes should look like this:
        //
        // parent::initConfig();
        // $this->mergeConfig(array(
        //     'option' => 'default value',
        // ));
        $this->_config = array();
    }

    /**
     * Check that given value of configuration option is valid
     *
     * @param string $name          Configuration option name
     * @param mixed $value          Option value (passed by reference)
     * @return boolean
     */
    protected function validateConfig($name, &$value)
    {
        // This method is mean to be overridden in a case if additional validation
        // of configuration option value should be performed before using it
        // Method should validate and, if required, normalize given value
        // of configuration option and return true if option can be used and false if not
        // It is important that this method will be:
        // - as simple as possible to optimize performance
        // - will not call other methods that attempts to modify or merge object configuration
        //   to avoid infinite loop
        // Normally this method should look like this:
        //
        // switch($name) {
        //     case 'option':
        //         // $value validation and normalization code
        //         break;
        //     default:
        //         return parent::validateConfig($name, $value);
        //         break;
        // }
        return (true);
    }

    /**
     * Perform required operations when configuration option value is changed
     *
     * @param string $name          Configuration option name
     * @param mixed $value          Configuration option value
     * @param boolean $merge        TRUE if configuration option is changed during merge process,
     *                              FALSE if it is changed by setting configuration option
     * @return void
     */
    protected function onConfigChange($name, $value, $merge)
    {
        // This method is mean to be overridden in a case if some kind of additional logic
        // is required to be performed upon setting value of configuration option.
    }

    /**
     * Bootstrap object configuration options
     *
     * @return void
     */
    protected final function bootstrapConfig()
    {
        if ((is_array($this->_config)) || ($this->_configInBootstrap)) {
            return;
        }
        $this->_configInBootstrap = true;
        $this->initConfig();
        $this->_configInBootstrap = false;
    }

    /**
     * Get Id of configuration class that is used for given class
     *
     * @return string
     */
    protected function getConfigClassId()
    {
        $class = get_class($this);
        if (!array_key_exists($class, self::$_configClassesMap)) {
            // Determine which class actually defines configuration for given class
            $reflection = new \ReflectionClass($class);
            $id = $reflection->getMethod('initConfig')->getDeclaringClass()->getName();
            self::$_configClassesMap[$class] = $id;
        }
        return (self::$_configClassesMap[$class]);
    }

    /**
     * Merge given configuration options with current configuration options
     *
     * @param array $config     Configuration options to merge
     * @return void
     */
    protected final function mergeConfig($config)
    {
        if (!is_array($this->_config)) {
            $this->bootstrapConfig();
        }
        if (!is_array($config)) {
            return;
        }
        foreach ($config as $key => $value) {
            if ((!$this->_configInBootstrap) && (!$this->validateConfig($key, $value))) {
                continue;
            }
            $this->_config[$key] = $value;
            if (!$this->_configInBootstrap) {
                $this->onConfigChange($key, $value, true);
            }
        }
    }

    /**
     * Attempt to convert given configuration information to array
     *
     * @param mixed $config     Value to convert to array
     * @param mixed $value      OPTIONAL Array entry value for inline array entry
     * @param boolean $inline   OPTIONAL TRUE to allow treating given string values as array entry
     * @return mixed
     */
    protected function configToArray($config, $value = null, $inline = false)
    {
        if (is_object($config)) {
            if (is_callable(array($config, 'toArray'))) {
                $config = $config->toArray();
            } elseif ($config instanceof \Iterator) {
                $temp = array();
                foreach ($config as $k => $v) {
                    $temp[$k] = $v;
                }
                $config = $temp;
            } elseif ($config instanceof \ArrayAccess) {
                $temp = array();
                foreach ($this->_config as $k => $v) {
                    if (($k === ConfigurableInterface::CLASS_ID_KEY) || (!$config->offsetExists($k))) {
                        continue;
                    }
                    $temp[$k] = $config->offsetGet($k);
                }
                $config = $temp;
            }
        }
        if (($inline) && (is_string($config)) && (strlen($config))) {
            $config = array($config => $value);
        }
        return $config;
    }

}

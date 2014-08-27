<?php

namespace Flying\Config;

/**
 * Base implementation of configurable class
 */
abstract class AbstractConfig implements ConfigurableInterface
{
    /**
     * Configuration options
     *
     * @var array
     */
    private $config = null;
    /**
     * List of configuration options that are not yet initialized
     *
     * @var array
     */
    private $configPendingLazyInit = array();
    /**
     * TRUE if configuration options bootstrap is being performed, FALSE otherwise
     *
     * @var boolean
     */
    private $configInBootstrap = false;
    /**
     * Mapping table between class name and its configuration options set
     *
     * @var array
     */
    private static $configCache = array(
        'classes_map' => array(),
        'config'      => array(),
        'lazy_init'   => array(),
    );

    /**
     * {@inheritdoc}
     */
    public function isConfigExists($name)
    {
        if (!is_array($this->config)) {
            $this->bootstrapConfig();
        }
        if ((is_string($name)) && ($name !== self::CLASS_ID_KEY)) {
            return array_key_exists($name, $this->config);
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig($config = null)
    {
        if (!is_array($this->config)) {
            $this->bootstrapConfig();
        }
        if ($config === null) {
            $this->resolveLazyConfigInit();
            $config = $this->config;
            $config[self::CLASS_ID_KEY] = $this->getConfigClassId();
            return $config;
        } elseif (is_string($config)) {
            // This is request for configuration option value
            if (array_key_exists($config, $this->config)) {
                $this->resolveLazyConfigInit($config);
                return $this->config[$config];
            } else {
                return null;
            }
        } elseif ((is_array($config)) &&
            (array_key_exists(self::CLASS_ID_KEY, $config)) && // This is repetitive call to getConfig()
            ($config[self::CLASS_ID_KEY] === $this->getConfigClassId())
        ) // Only classes with same configuration class Id can share configurations
        {
            return $config;
        }
        // This is request for configuration (with possible merging)
        $config = $this->configToArray($config);
        if (!is_array($config)) {
            $config = array();
        }
        $this->resolveLazyConfigInit();
        $result = $this->config;
        $result[self::CLASS_ID_KEY] = $this->getConfigClassId();
        foreach ($config as $name => $value) {
            if ((!array_key_exists($name, $result)) || ($name === self::CLASS_ID_KEY)) {
                continue;
            }
            if ($this->validateConfig($name, $value)) {
                $result[$name] = $value;
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig($config, $value = null)
    {
        if (!is_array($this->config)) {
            $this->bootstrapConfig();
        }
        $config = $this->configToArray($config, $value, true);
        if ((!is_array($config)) || (!sizeof($config))) {
            return;
        }
        foreach ($config as $key => $value) {
            if (!array_key_exists($key, $this->config)) {
                continue;
            }
            if (!$this->validateConfig($key, $value)) {
                continue;
            }
            $this->config[$key] = $value;
            unset($this->configPendingLazyInit[$key]);
            $this->onConfigChange($key, $value, false);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function modifyConfig($config, $modification, $value = null)
    {
        // Call getConfig() for given configuration options, but only if it is necessary
        // Without this check it is possible to get infinite recursion loop in a case
        // if getConfig() is overridden and calls modifyConfig() by itself
        if ((!is_array($config)) ||
            (!array_key_exists(self::CLASS_ID_KEY, $config)) ||
            ($config[self::CLASS_ID_KEY] !== $this->getConfigClassId())
        ) {
            $config = $this->getConfig($config);
        }
        $modification = $this->configToArray($modification, $value, true);
        if ((!is_array($modification)) || (!sizeof($modification))) {
            return $config;
        }
        foreach ($modification as $name => $value) {
            if (!array_key_exists($name, $this->config)) {
                continue;
            }
            if ($this->validateConfig($name, $value)) {
                $config[$name] = $value;
            }
        }
        return $config;
    }

    /**
     * Initialize list of configuration options
     *
     * This method is mean to be overridden to provide configuration options set.
     * To allow inheritance of configuration options sets across several levels
     * of inherited classes - this method in nested classes should look like this:
     *
     * <code>
     * parent::initConfig();
     * $this->mergeConfig(array(
     *     'option' => 'default value',
     * ));
     * </code>
     *
     * @return void
     */
    protected function initConfig()
    {
        $this->config = array();
    }

    /**
     * Perform "lazy initialization" of configuration option with given name
     *
     * @param string $name Configuration option name
     * @return mixed
     */
    protected function lazyConfigInit($name)
    {
        return null;
    }

    /**
     * Check that given value of configuration option is valid
     *
     * This method is mean to be overridden in a case if additional validation
     * of configuration option value should be performed before using it
     * Method should validate and, if required, normalize given value
     * of configuration option and return true if option can be used and false if not
     * It is important that this method will be:
     * - as simple as possible to optimize performance
     * - will not call other methods that attempts to modify or merge object configuration
     * to avoid infinite loop
     * Normally this method should look like this:
     *
     * <code>
     * switch($name) {
     *      case 'option':
     *          // $value validation and normalization code
     *          break;
     *      default:
     *          return parent::validateConfig($name, $value);
     *          break;
     * }
     * </code>
     *
     * @param string $name Configuration option name
     * @param mixed $value Option value (passed by reference)
     * @return boolean
     */
    protected function validateConfig($name, &$value)
    {
        return true;
    }

    /**
     * Perform required operations when configuration option value is changed
     *
     * This method is mean to be overridden in a case if some kind of additional logic
     * is required to be performed upon setting value of configuration option.
     *
     * @param string $name          Configuration option name
     * @param mixed $value          Configuration option value
     * @param boolean $merge        TRUE if configuration option is changed during merge process,
     *                              FALSE if it is changed by setting configuration option
     * @return void
     */
    protected function onConfigChange($name, $value, $merge)
    {
    }

    /**
     * Bootstrap object configuration options
     *
     * @return void
     */
    protected function bootstrapConfig()
    {
        if ((is_array($this->config)) || ($this->configInBootstrap)) {
            return;
        }
        $this->configInBootstrap = true;
        $id = $this->getConfigClassId();
        if (!array_key_exists($id, self::$configCache['config'])) {
            $this->initConfig();
            $lazy = array();
            foreach ($this->config as $name => $value) {
                if ($value === null) {
                    $lazy[$name] = true;
                }
            }
            self::$configCache['config'][$id] = $this->config;
            self::$configCache['lazy_init'][$id] = $lazy;
        }
        $this->config = self::$configCache['config'][$id];
        $this->configPendingLazyInit = self::$configCache['lazy_init'][$id];
        $this->configInBootstrap = false;
    }

    /**
     * Get Id of configuration class that is used for given class
     *
     * @return string
     */
    protected function getConfigClassId()
    {
        $class = get_class($this);
        if (!array_key_exists($class, self::$configCache['classes_map'])) {
            // Determine which class actually defines configuration for given class
            // It is highly uncommon, but still possible situation when class
            // have no initConfig() method, so its configuration is completely inherited from parent,
            // but has validateConfig() method, so initial state of configuration can be different
            // from its parent.
            // To handle this properly we need to find earliest parent class that have either initConfig()
            // ot validateConfig() method and use is as a mapping target for current class
            $reflection = new \ReflectionClass($class);
            $c = $class;
            do {
                if (($reflection->getMethod('initConfig')->getDeclaringClass()->getName() === $c) ||
                    ($reflection->getMethod('validateConfig')->getDeclaringClass()->getName() === $c)
                ) {
                    break;
                }
                $reflection = $reflection->getParentClass();
                $c = $reflection->getName();
            } while ($reflection);
            self::$configCache['classes_map'][$class] = $reflection->getName();
        }
        return self::$configCache['classes_map'][$class];
    }

    /**
     * Merge given configuration options with current configuration options
     *
     * @param array $config Configuration options to merge
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function mergeConfig(array $config)
    {
        if (!$this->configInBootstrap) {
            throw new \RuntimeException('mergeConfig() can only be used for configuration initialization');
        }
        if ((is_int(key($config))) && (array_keys($config) === range(0, sizeof($config) - 1))) {
            // Configuration is defined as array of keys with lazy initialization
            $temp = array();
            foreach ($config as $key) {
                if (!is_string($key)) {
                    throw new \InvalidArgumentException('Configuration option name must be a string');
                }
                $temp[$key] = null;
            }
            $config = $temp;
        }
        foreach ($config as $key => $value) {
            if ($value !== null) {
                if (!is_scalar($value)) {
                    throw new \InvalidArgumentException(sprintf('Non-scalar initial value for configuration option "%s" for class "%s"', $key, get_class($this)));
                }
                if (!$this->validateConfig($key, $value)) {
                    throw new \RuntimeException(sprintf('Invalid initial value for configuration option "%s" for class "%s"', $key, get_class($this)));
                }
            }
            $this->config[$key] = $value;
        }
    }

    /**
     * Attempt to convert given configuration information to array
     *
     * @param mixed $config   Value to convert to array
     * @param mixed $value    OPTIONAL Array entry value for inline array entry
     * @param boolean $inline OPTIONAL TRUE to allow treating given string values as array entry
     * @return mixed
     */
    protected function configToArray($config, $value = null, $inline = false)
    {
        if (is_object($config)) {
            if (is_callable(array($config, 'toArray'))) {
                $config = $config->toArray();
            } elseif ($config instanceof \Iterator) {
                $config = iterator_to_array($config, true);
            } elseif ($config instanceof \ArrayAccess) {
                $temp = array();
                foreach ($this->config as $k => $v) {
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

    /**
     * Resolve lazy initialization of configuration options
     *
     * @param string $name OPTIONAL Configuration option to perform lazy initialization of
     * @throws \RuntimeException
     * @return void
     */
    protected function resolveLazyConfigInit($name = null)
    {
        if (!sizeof($this->configPendingLazyInit)) {
            return;
        }
        if ($name !== null) {
            $options = (array_key_exists($name, $this->configPendingLazyInit)) ? array($name) : array();
        } else {
            $options = array_keys($this->configPendingLazyInit);
        }
        foreach ($options as $name) {
            $value = $this->lazyConfigInit($name);
            if ($this->validateConfig($name, $value)) {
                $this->config[$name] = $value;
                unset($this->configPendingLazyInit[$name]);
            } else {
                throw new \RuntimeException('Lazily initialized configuration option "' . $name . '" is not passed validation check');
            }
        }
    }
}

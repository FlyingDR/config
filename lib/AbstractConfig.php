<?php

namespace Flying\Config;

/**
 * Base implementation of configurable class
 */
abstract class AbstractConfig implements ConfigurableInterface
{
    /**
     * Mapping table between class name and its configuration options set
     *
     * @var array
     */
    private static $configCache = [
        'classes_map' => [],
        'config'      => [],
        'lazy_init'   => [],
    ];
    /**
     * Configuration options
     *
     * @var array
     */
    private $config;
    /**
     * List of configuration options that are not yet initialized
     *
     * @var array
     */
    private $configPendingLazyInit = [];
    /**
     * TRUE if configuration options bootstrap is being performed, FALSE otherwise
     *
     * @var boolean
     */
    private $configInBootstrap = false;

    /**
     * {@inheritdoc}
     */
    public function isConfigExists($name)
    {
        if (!is_array($this->config)) {
            $this->bootstrapConfig();
        }
        if (is_string($name) && ($name !== self::CLASS_ID_KEY)) {
            return array_key_exists($name, $this->config);
        }
        return false;
    }

    /**
     * Bootstrap object configuration options
     *
     * @return void
     */
    protected function bootstrapConfig()
    {
        if (is_array($this->config) || $this->configInBootstrap) {
            return;
        }
        $this->configInBootstrap = true;
        $id = $this->getConfigClassId();
        if (!array_key_exists($id, self::$configCache['config'])) {
            $this->initConfig();
            $lazy = [];
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
            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                return \stdClass::class;
            }
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
     * Initialize list of configuration options
     *
     * This method is mean to be overridden to provide configuration options set.
     * To allow inheritance of configuration options sets across several levels
     * of inherited classes - this method in nested classes should look like this:
     *
     * <code>
     * parent::initConfig();
     * $this->mergeConfig([
     *     'option' => 'default value',
     * ]);
     * </code>
     *
     * @return void
     */
    protected function initConfig()
    {
        $this->config = [];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyConfig(array $config, $modification, $value = null)
    {
        // Call getConfig() for given configuration options, but only if it is necessary
        // Without this check it is possible to get infinite recursion loop in a case
        // if getConfig() is overridden and calls modifyConfig() by itself
        if ((!array_key_exists(self::CLASS_ID_KEY, $config)) ||
            ($config[self::CLASS_ID_KEY] !== $this->getConfigClassId())
        ) {
            $config = $this->getConfig($config);
        }
        $modification = $this->configToArray($modification, $value, true);
        if ((!is_array($modification)) || (!count($modification))) {
            return $config;
        }
        foreach ($modification as $mName => $mValue) {
            if (!array_key_exists($mName, $this->config)) {
                continue;
            }
            if ($this->validateConfig($mName, $mValue)) {
                $config[$mName] = $mValue;
            }
        }
        return $config;
    }

    /**
     * {@inheritdoc}
     * @param string|array|null $config
     * @param boolean $export
     * @return mixed
     * @throws \RuntimeException
     */
    public function getConfig($config = null, $export = false)
    {
        if (!is_array($this->config)) {
            $this->bootstrapConfig();
        }
        if (is_string($config)) {
            // This is request for configuration option value
            if (array_key_exists($config, $this->config)) {
                if (array_key_exists($config, $this->configPendingLazyInit)) {
                    $this->resolveLazyConfigInit($config);
                }
                return $this->config[$config];
            }
            return null;
        }

        if ($config === null) {
            // This is request for complete configuration options set
            $this->resolveLazyConfigInit();
            $config = $this->config;
            if (!$export) {
                $config[self::CLASS_ID_KEY] = $this->getConfigClassId();
            }
            return $config;
        }

        if (is_array($config) &&
            array_key_exists(self::CLASS_ID_KEY, $config) &&
            ($config[self::CLASS_ID_KEY] === $this->getConfigClassId())) {
            // This is repetitive call to getConfig()
            return $config;
        }

        // This is request for configuration (with possible merging)
        $config = $this->configToArray($config);
        if (!is_array($config)) {
            $config = [];
        }
        $this->resolveLazyConfigInit();
        $result = $this->config;
        if (!$export) {
            $result[self::CLASS_ID_KEY] = $this->getConfigClassId();
        }
        foreach ($config as $name => $value) {
            if ($name !== self::CLASS_ID_KEY && array_key_exists($name, $result) && $this->validateConfig($name, $value)) {
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
        if (is_array($config)) {
            foreach ($config as $ck => $cv) {
                if (array_key_exists($ck, $this->config) && $this->validateConfig($ck, $cv)) {
                    $this->config[$ck] = $cv;
                    unset($this->configPendingLazyInit[$ck]);
                    $this->onConfigChange($ck, $cv);
                }
            }
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
            if (is_callable([$config, 'toArray'])) {
                $config = $config->toArray();
            } elseif ($config instanceof \Iterator) {
                $config = iterator_to_array($config, true);
            } elseif ($config instanceof \ArrayAccess) {
                $temp = [];
                foreach ($this->config as $k => $v) {
                    if ($k !== ConfigurableInterface::CLASS_ID_KEY && $config->offsetExists($k)) {
                        $temp[$k] = $config->offsetGet($k);
                    }
                }
                $config = $temp;
            }
        }
        if ($inline && is_string($config) && '' !== $config) {
            $config = [$config => $value];
        }
        return $config;
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
     * @param string $name Configuration option name
     * @param mixed $value Configuration option value
     * @return void
     */
    protected function onConfigChange($name, $value)
    {
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
        if (!count($this->configPendingLazyInit)) {
            return;
        }
        if ($name !== null) {
            $options = array_key_exists($name, $this->configPendingLazyInit) ? [$name] : [];
        } else {
            $options = array_keys($this->configPendingLazyInit);
        }
        foreach ($options as $oName) {
            $value = $this->lazyConfigInit($oName);
            if ($this->validateConfig($oName, $value)) {
                $this->config[$oName] = $value;
                unset($this->configPendingLazyInit[$oName]);
            } else {
                throw new \RuntimeException('Lazily initialized configuration option "' . $oName . '" is not passed validation check');
            }
        }
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
        if (is_int(key($config)) && (array_keys($config) === range(0, count($config) - 1))) {
            // Configuration is defined as array of keys with lazy initialization
            if (array_reduce($config, function ($n, $v) {
                return $n || !is_string($v);
            }, false)) {
                throw new \InvalidArgumentException('Lazy configuration should be list of string configuration keys');
            }
            $this->config = array_merge($this->config, array_fill_keys($config, null));
        } else {
            // Configuration is given as normal key->value array
            foreach ($config as $key => $value) {
                if ($value !== null) {
                    if ((!is_scalar($value)) && (!is_array($value))) {
                        throw new \InvalidArgumentException(sprintf('Non-scalar initial value for configuration option "%s" for class "%s"', $key, get_class($this)));
                    }
                    if (!$this->validateConfig($key, $value)) {
                        throw new \RuntimeException(sprintf('Invalid initial value for configuration option "%s" for class "%s"', $key, get_class($this)));
                    }
                }
                $this->config[$key] = $value;
            }
        }
    }
}

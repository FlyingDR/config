<?php

namespace Flying\Config;

/**
 * Standalone implementation of object configuration
 */
class ObjectConfig extends AbstractConfig
{
    /**
     * Owner of this configuration object
     *
     * @var object
     */
    private $owner = null;
    /**
     * Configuration options for to serve by this configuration object
     *
     * @var array
     */
    private $options;
    /**
     * Configuration class Id for this configuration object
     *
     * @var string
     */
    private $classId = null;
    /**
     * List of registered callbacks for customizing configuration object behavior
     *
     * @var array
     */
    private $callbacks = [
        'validateConfig' => null, // Custom implementation of validateConfig()
        'onConfigChange' => null, // Custom implementation of onConfigChange()
        'lazyConfigInit' => null, // Custom implementation of lazyConfigInit()
    ];

    /**
     * Class constructor
     *
     * @param object $owner    Owner of this configuration object
     * @param array $options   List of configuration options to serve (@see AbstractConfig::initConfig for description)
     * @param array $callbacks OPTIONAL List of callbacks to customize configuration object behavior
     * @param array $config    OPTIONAL Configuration options to initialize class with
     * @throws \InvalidArgumentException
     */
    public function __construct($owner, array $options, array $callbacks = [], array $config = [])
    {
        $this->setOwner($owner);
        $this->options = $options;
        foreach ($callbacks as $type => $callback) {
            if (!array_key_exists($type, $this->callbacks)) {
                throw new \InvalidArgumentException('Unknown customization callback type: ' . $type);
            }
            // If method name is passed instead of callback - create callback from it
            if (is_string($callback) && method_exists($this->owner, $callback)) {
                $callback = [$this->owner, $callback];
            }
            if (!is_callable($callback)) {
                throw new \InvalidArgumentException('Non-callable callback is given for customization callback type: ' . $type);
            }
            $this->callbacks[$type] = $callback;
        }
        $this->bootstrapConfig();
        $this->setConfig($config);
    }

    /**
     * Set owner of this configuration object
     *
     * @param object $owner Owner of this configuration object
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function setOwner($owner)
    {
        if (!is_object($owner)) {
            throw new \InvalidArgumentException('Given owner of configuration object is not an object');
        }
        $this->owner = $owner;
        $this->classId = null; // Reset class Id because we have new owner
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigClassId()
    {
        if (!$this->classId) {
            $this->classId = get_class($this->owner);
        }
        return $this->classId;
    }

    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig($this->options);
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfig($name, &$value)
    {
        if ($this->callbacks['validateConfig'] !== null) {
            /** @noinspection VariableFunctionsUsageInspection */
            return call_user_func_array(
                $this->callbacks['validateConfig'],
                [$name, &$value]
            );
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function onConfigChange($name, $value)
    {
        if ($this->callbacks['onConfigChange'] !== null) {
            call_user_func($this->callbacks['onConfigChange'], $name, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function lazyConfigInit($name)
    {
        if ($this->callbacks['lazyConfigInit'] !== null) {
            return call_user_func($this->callbacks['lazyConfigInit'], $name);
        }
        return null;
    }
}

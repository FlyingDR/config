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
    private $options = array();
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
    private $callbacks = array(
        'validateConfig' => null, // Custom implementation of validateConfig()
        'onConfigChange' => null, // Custom implementation of onConfigChange()
        'lazyConfigInit' => null, // Custom implementation of lazyConfigInit()
    );

    /**
     * Class constructor
     *
     * @param object $owner    Owner of this configuration object
     * @param array $options   List of configuration options to serve (@see AbstractConfig::initConfig for description)
     * @param array $callbacks OPTIONAL List of callbacks to customize configuration object behavior
     * @param array $config    OPTIONAL Configuration options to initialize class with
     * @throws \InvalidArgumentException
     */
    public function __construct($owner, $options, $callbacks = null, $config = null)
    {
        $this->setOwner($owner);
        if (!is_array($options)) {
            throw new \InvalidArgumentException('Configuration options list must be an array');
        }
        $this->options = $options;
        if (is_array($callbacks)) {
            foreach ($callbacks as $type => $callback) {
                if (!array_key_exists($type, $this->callbacks)) {
                    throw new \InvalidArgumentException('Unknown customization callback type: ' . $type);
                }
                // If method name is passed instead of callback - create callback from it
                if (is_string($callback)) {
                    $callback = array($this->owner, $callback);
                }
                if (!is_callable($callback)) {
                    throw new \InvalidArgumentException('Non-callable callback is given for customization callback type: ' . $type);
                }
                $this->callbacks[$type] = $callback;
            }
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
        if ($this->callbacks['validateConfig']) {
            return call_user_func_array(
                $this->callbacks['validateConfig'],
                array($name, &$value)
            );
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function onConfigChange($name, $value, $merge)
    {
        if ($this->callbacks['onConfigChange']) {
            call_user_func_array(
                $this->callbacks['onConfigChange'],
                array($name, $value, $merge)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function lazyConfigInit($name)
    {
        if ($this->callbacks['lazyConfigInit']) {
            return call_user_func_array(
                $this->callbacks['lazyConfigInit'],
                array($name)
            );
        }
        return null;
    }
}

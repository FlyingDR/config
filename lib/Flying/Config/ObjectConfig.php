<?php

namespace Flying\Config;

/**
 * Standalone implementation of object configuration
 */
class ObjectConfig extends AbstractConfig
{
    /**
     * Owner of this configuration object
     * @var object $_owner
     */
    protected $_owner = null;
    /**
     * Configuration options for to serve by this configuration object
     * @var array $_options
     */
    protected $_options = array();
    /**
     * Configuration class Id for this configuration object
     * @var string $_classId
     */
    protected $_classId = null;
    /**
     * List of registered callbacks for customizing configuration object behavior
     * @var array $_callbacks
     */
    protected $_callbacks = array(
        'checkConfig'     => null, // Custom implementation of _checkConfig()
        'onConfigChanged' => null, // Custom implementation of _onConfigChanged()
    );

    /**
     * Class constructor
     *
     * @param object $owner         Owner of this configuration object
     * @param array $options        List of configuration options to serve (@see AbstractConfig::_initConfig for description)
     * @param array $callbacks      OPTIONAL List of callbacks to customize configuration object behavior
     * @param array $config         OPTIONAL Configuration options to initialize class with
     * @throws \InvalidArgumentException
     * @return ObjectConfig
     */
    public function __construct($owner, $options, $callbacks = null, $config = null)
    {
        $this->_setOwner($owner);
        if (!is_array($options)) {
            throw new \InvalidArgumentException('Configuration options list must be an array');
        }
        $this->_options = $options;
        if (is_array($callbacks)) {
            foreach ($callbacks as $type => $callback) {
                if (!array_key_exists($type, $this->_callbacks)) {
                    throw new \InvalidArgumentException('Unknown customization callback type: ' . $type);
                }
                // If method name is passed instead of callback - create callback from it
                if (is_string($callback)) {
                    $callback = array($this->_owner, $callback);
                }
                if (!is_callable($callback)) {
                    throw new \InvalidArgumentException('Non-callable callback is given for customization callback type: ' . $type);
                }
                $this->_callbacks[$type] = $callback;
            }
        }
        $this->_bootstrapConfig();
        $this->setConfig($config);
    }

    /**
     * Set owner of this configuration object
     *
     * @param object $owner     Owner of this configuration object
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function _setOwner($owner)
    {
        if (!is_object($owner)) {
            throw new \InvalidArgumentException('Given owner of embedded configuration object is not an object');
        }
        $this->_owner = $owner;
        $this->_classId = null; // Reset class Id because we have new owner
    }

    /**
     * Get Id of configuration class that is used for given class
     *
     * @return string
     */
    protected function _getConfigClassId()
    {
        if (!$this->_classId) {
            $this->_classId = get_class($this->_owner);
        }
        return ($this->_classId);
    }

    /**
     * Initialize list of configuration options
     *
     * @return void
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig($this->_options);
    }

    /**
     * Check that given value of configuration option is valid
     *
     * @param string $name          Configuration option name
     * @param mixed $value          Option value (passed by reference)
     * @param string $operation     Current operation Id
     * @return boolean
     */
    protected function _checkConfig($name, &$value, $operation)
    {
        if ($this->_callbacks['checkConfig']) {
            return (call_user_func_array(
                $this->_callbacks['checkConfig'],
                array($name, &$value, $operation)
            ));
        }
        return (true);
    }

    /**
     * Perform required operations when configuration option value is changed
     *
     * @param string $name          Configuration option name
     * @param mixed $value          Configuration option value
     * @param string $operation     Current operation Id
     * @return void
     */
    protected function _onConfigChanged($name, $value, $operation)
    {
        if ($this->_callbacks['onConfigChanged']) {
            call_user_func_array(
                $this->_callbacks['onConfigChanged'],
                array($name, $value, $operation)
            );
        }
    }

}

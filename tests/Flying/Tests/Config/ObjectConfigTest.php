<?php

namespace Flying\Tests\Config;

use Flying\Config\ObjectConfig;
use Flying\Tests\Config\Fixtures\ConfigurableObject;

/**
 * Test for standalone configuration object
 */
class ObjectConfigTest extends BaseConfigTest
{

    public function testMissedOwnerForConfigObjectCreation()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Given owner of configuration object is not an object');
        new ObjectConfig(null, null);
    }

    public function testMissedConfigurationForConfigObjectCreation()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Configuration options list must be an array');
        new ObjectConfig($this, null);
    }

    public function testUnknownCallbackPassingForConfigObjectCreation()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Unknown customization callback type: unknown');
        new ObjectConfig($this, array(), array(
            'unknown' => null,
        ));
    }

    public function testInvalidCallbackPassingForConfigObjectCreation()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Non-callable callback is given for customization callback type: validateConfig');
        new ObjectConfig($this, array(), array(
            'validateConfig' => 'unavailableMethod',
        ));
    }

    public function testNonCallableCallbackPassingForConfigObjectCreation()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Non-callable callback is given for customization callback type: validateConfig');
        new ObjectConfig($this, array(), array(
            'validateConfig' => array(),
        ));
    }

    public function testConfigInitializationInConstructor()
    {
        $config = new ObjectConfig($this, array(
            'a' => 'abc',
        ), array(), array(
            'a' => 'xyz',
            'b' => 123,
        ));
        $this->validateConfig($config->getConfig(), array(
            'a' => 'xyz',
        ), get_class($this));
    }

    /**
     * Get configuration object to test
     *
     * @return ConfigurableObject
     */
    protected function getConfigObject()
    {
        return new ConfigurableObject();
    }

}

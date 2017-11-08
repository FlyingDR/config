<?php

namespace Flying\Tests\Config;

use Flying\Config\ObjectConfig;
use Flying\Tests\Config\Fixtures\ConfigurableObject;

/**
 * Test for standalone configuration object
 */
class ObjectConfigTest extends BaseConfigTest
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Given owner of configuration object is not an object
     */
    public function testMissedOwnerForConfigObjectCreation()
    {
        new ObjectConfig(null, []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown customization callback type: unknown
     */
    public function testUnknownCallbackPassingForConfigObjectCreation()
    {
        new ObjectConfig($this, [], [
            'unknown' => null,
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Non-callable callback is given for customization callback type: validateConfig
     */
    public function testInvalidCallbackPassingForConfigObjectCreation()
    {
        new ObjectConfig($this, [], [
            'validateConfig' => 'unavailableMethod',
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Non-callable callback is given for customization callback type: validateConfig
     */
    public function testNonCallableCallbackPassingForConfigObjectCreation()
    {
        new ObjectConfig($this, [], [
            'validateConfig' => [],
        ]);
    }

    public function testConfigInitializationInConstructor()
    {
        $config = new ObjectConfig($this, [
            'a' => 'abc',
        ], [], [
            'a' => 'xyz',
            'b' => 123,
        ]);
        $this->validateConfig($config->getConfig(), [
            'a' => 'xyz',
        ], get_class($this));
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

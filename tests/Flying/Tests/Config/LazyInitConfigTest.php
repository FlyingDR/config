<?php

namespace Flying\Tests\Config;

use Flying\Tests\Config\Fixtures\CallbackLog;
use Flying\Tests\Config\Fixtures\LazyInitConfig;
use Flying\Tests\Config\Fixtures\LazyInitConfigWithInvalidValues;
use Flying\Tests\Config\Fixtures\LazyInitConfigWithRejectedValidation;

class LazyInitConfigTest extends AbstractConfigTest
{
    private static $configReference = [
        'string_option'  => 'some value',
        'boolean_option' => true,
        'int_option'     => 42,
    ];
    private static $configModifications = [
        'string_option'  => 'modified value',
        'boolean_option' => null,
        'int_option'     => 12345,
    ];
    private static $configExpected = [
        'string_option'  => 'modified value',
        'boolean_option' => false,
        'int_option'     => 12345,
    ];

    public function testLazyInitOnGettingSingleConfigValue()
    {
        $object = $this->getConfigObject();
        foreach (self::$configReference as $name => $value) {
            $this->assertEquals($object->getConfig($name), $value);
        }
    }

    public function testLazyInitCallbackCallLog()
    {
        $this->runLazyConfigInitCallbackTest($this->getConfigObject(), self::$configReference);
        $this->runLazyConfigInitCallbackTest($this->getConfigObject(), self::$configReference, false);
    }

    public function testSettingConfigOptionsShouldResetLazyInit()
    {
        $object = $this->getConfigObject();
        $logger = new CallbackLog();
        $method = 'lazyConfigInit';
        $object->setCallbackLogger($method, $logger);
        $object->setConfig(self::$configModifications);
        $this->validateConfig($object->getConfig(), self::$configExpected);
        $this->assertEmpty($logger->get());
    }

    public function testLazyInitWithInvalidValuesShouldResultInValidConfig()
    {
        $object = new LazyInitConfigWithInvalidValues();
        $this->validateConfig($object->getConfig(), array_merge(self::$configReference, [
            'should_be_boolean' => true,
            'should_be_int'     => 123,
            'should_be_string'  => '12345',
        ]), get_class($object));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLazyInitWithRejectedValidationRaisesException()
    {
        $object = new LazyInitConfigWithRejectedValidation();
        $object->getConfig();
    }

    /**
     * Get configuration object to test
     *
     * @return LazyInitConfig
     */
    protected function getConfigObject()
    {
        return new LazyInitConfig();
    }
}

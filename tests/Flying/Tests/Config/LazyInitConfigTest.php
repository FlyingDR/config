<?php

namespace Flying\Tests\Config;

use Flying\Tests\Config\Fixtures\CallbackLog;
use Flying\Tests\Config\Fixtures\LazyInitConfig;
use Flying\Tests\Config\Fixtures\LazyInitConfigWithInvalidValues;
use Flying\Tests\Config\Fixtures\LazyInitConfigWithRejectedValidation;

class LazyInitConfigTest extends AbstractConfigTest
{
    protected $_configReference = array(
        'string_option'  => 'some value',
        'boolean_option' => true,
        'int_option'     => 42,
    );
    protected $_configModifications = array(
        'string_option'  => 'modified value',
        'boolean_option' => null,
        'int_option'     => 12345,
    );
    protected $_configExpected = array(
        'string_option'  => 'modified value',
        'boolean_option' => false,
        'int_option'     => 12345,
    );

    public function testLazyInitOnGettingSingleConfigValue()
    {
        $object = $this->getConfigObject();
        foreach ($this->_configReference as $name => $value) {
            $this->assertEquals($object->getConfig($name), $value);
        }
    }

    public function testLazyInitCallbackCallLog()
    {
        $this->runLazyConfigInitCallbackTest($this->getConfigObject(), $this->_configReference, true);
        $this->runLazyConfigInitCallbackTest($this->getConfigObject(), $this->_configReference, false);
    }

    public function testSettingConfigOptionsShouldResetLazyInit()
    {
        $object = $this->getConfigObject();
        $logger = new CallbackLog();
        $method = 'lazyConfigInit';
        $object->setCallbackLogger($method, $logger);
        $object->setConfig($this->_configModifications);
        $this->validateConfig($object->getConfig(), $this->_configExpected);
        $this->assertEmpty($logger->get());
    }

    public function testLazyInitWithInvalidValuesShouldResultInValidConfig()
    {
        $object = new LazyInitConfigWithInvalidValues();
        $this->validateConfig($object->getConfig(), array_merge($this->_configReference, array(
            'should_be_boolean' => true,
            'should_be_int'     => 123,
            'should_be_string'  => '12345',
        )), get_class($object));
    }

    public function testLazyInitWithRejectedValidationRaisesException()
    {
        $object = new LazyInitConfigWithRejectedValidation();
        $this->setExpectedException('\RuntimeException');
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

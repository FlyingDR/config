<?php

namespace Flying\Tests\Config;

use Flying\Tests\Config\Fixtures\CallbackLog;
use Flying\Tests\Config\Fixtures\LazyInitConfig;

class LazyInitConfigTest extends AbstractConfigTest
{
    protected $_configReference = array(
        'string_option'  => 'some value',
        'boolean_option' => true,
        'int_option'     => 42,
        'rejected'       => 'abc',
    );
    protected $_configModifications = array(
        'string_option'  => 'modified value',
        'boolean_option' => null,
        'int_option'     => 12345,
        'rejected'       => 'xyz',
    );
    protected $_configExpected = array(
        'string_option'  => 'modified value',
        'boolean_option' => false,
        'int_option'     => 12345,
        'rejected'       => 'abc',
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
        // 'rejected' configuration option is not changed by setConfig()
        // so we should get one lazy initialization - for this option
        $log = $logger->get();
        $this->assertEquals(sizeof($log), 1);
        $log = array_shift($log);
        $this->assertTrue($log === array($method, 'rejected'));
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

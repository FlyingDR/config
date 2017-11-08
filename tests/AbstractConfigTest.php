<?php

namespace Flying\Tests\Config;

use Flying\Config\ConfigurableInterface;
use Flying\Tests\Config\Fixtures\BasicConfig;
use Flying\Tests\Config\Fixtures\CallbackLog;
use Flying\Tests\Config\Fixtures\CallbackTrackingInterface;
use Flying\Config\AbstractConfig;

abstract class AbstractConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @inheritdoc
     */
    public function tearDown()
    {
        // Clear cached information in configuration class to avoid side effects
        $reflection = new \ReflectionClass(AbstractConfig::class);
        $cacheProperty = $reflection->getProperty('configCache');
        $cacheProperty->setAccessible(true);
        $cache = $cacheProperty->getValue(new BasicConfig());
        foreach(array_keys($cache) as $key) {
            $cache[$key] = [];
        }
        $cacheProperty->setValue($cache);
        $cacheProperty->setAccessible(false);
    }

    /**
     * Get configuration object to test
     *
     * @return ConfigurableInterface
     */
    abstract protected function getConfigObject();

    /**
     * Validate configuration
     *
     * @param array $received Received configuration
     * @param array $expected Expected configuration
     * @param string $classId OPTIONAL Expected class Id value into received configuration
     * @return void
     */
    protected function validateConfig($received, $expected, $classId = null)
    {
        if ($classId === null) {
            $classId = get_class($this->getConfigObject());
        }
        $expected[ConfigurableInterface::CLASS_ID_KEY] = $classId;
        $this->assertInternalType('array', $received);
        $this->assertEquals(count($received), count($expected));
        foreach ($expected as $ek => $ev) {
            $this->assertArrayHasKey($ek, $received);
            $this->assertSame($ev, $received[$ek]);
            unset($received[$ek]);
        }
        if (count($received)) {
            $this->fail('Unexpected fields: ' . implode(', ', array_keys($received)));
        }
    }

    /**
     * Run tests of 'onConfigChange' callback method in given class
     *
     * @param CallbackTrackingInterface $object Test object instance
     * @param array $reference
     * @return void
     */
    protected function runOnConfigChangeCallbackTest(CallbackTrackingInterface $object, array $reference)
    {
        $logger = new CallbackLog();
        $method = 'onConfigChange';
        $object->setCallbackLogger($method, $logger);
        foreach ($reference as $name => $value) {
            $object->setConfig($name, $value);
        }
        $log = $logger->get();
        foreach ($reference as $name => $value) {
            $expected = [$method, $name, $value];
            $actual = array_shift($log);
            $this->assertSame($expected, $actual);
        }
    }

    /**
     * Run tests of 'lazyConfigInit' callback method in given class
     *
     * @param CallbackTrackingInterface $object     Test object instance
     * @param array $reference                      Reference configuration options to use for test
     * @param boolean $single                       OPTIONAL TRUE to test using retrieval of single configuration option,
     *                                              FALSE to use complete configuration options retrieval test
     * @return void
     */
    protected function runLazyConfigInitCallbackTest(CallbackTrackingInterface $object, array $reference, $single = true)
    {
        $logger = new CallbackLog();
        $method = 'lazyConfigInit';
        $object->setCallbackLogger($method, $logger);
        for ($i = 0; $i < 3; $i++) {
            // Run configuration options retrieving several times
            // We expect that lazyConfigInit() is called only once for each option
            if ($single) {
                foreach (array_keys($reference) as $name) {
                    $object->getConfig($name);
                }
            } else {
                $object->getConfig();
            }
        }
        $log = $logger->get();
        foreach (array_keys($reference) as $name) {
            $expected = [$method, $name];
            $actual = array_shift($log);
            $this->assertSame($expected, $actual);
        }
    }
}

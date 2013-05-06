<?php

namespace Flying\Tests\Config;

use Flying\Config\ConfigurableInterface;

abstract class AbstractConfigTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Get configuration object to test
     *
     * @return ConfigurableInterface
     */
    abstract protected function getConfigObject();

    /**
     * Validate configuration
     *
     * @param array $received   Received configuration
     * @param array $expected   Expected configuration
     * @param string $classId   OPTIONAL Expected class Id value into received configuration
     * @return void
     */
    protected function validateConfig($received, $expected, $classId = null)
    {
        if ($classId === null) {
            $classId = get_class($this->getConfigObject());
        }
        $expected[ConfigurableInterface::CLASS_ID_KEY] = $classId;
        $this->assertInternalType('array', $received);
        $this->assertEquals(sizeof($received), sizeof($expected));
        foreach ($expected as $ek => $ev) {
            $this->assertArrayHasKey($ek, $received);
            $this->assertTrue($ev === $received[$ek]);
            unset($received[$ek]);
        }
        if (sizeof($received)) {
            $this->fail('Unexpected fields: ' . join(', ', array_keys($received)));
        }
    }

}

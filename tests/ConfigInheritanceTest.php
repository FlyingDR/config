<?php

namespace Flying\Tests\Config;

use Flying\Tests\Config\Fixtures\A;
use Flying\Tests\Config\Fixtures\B;
use Flying\Tests\Config\Fixtures\C;
use Flying\Tests\Config\Fixtures\D;

/**
 * Test for configuration objects work with inheritance
 */
class ConfigInheritanceTest extends AbstractConfigTest
{
    private static $aReference = [
        'inherited' => 'A',
        'from_a'    => 'A',
    ];
    private static $bReference = [
        'inherited' => 'B',
        'from_a'    => 'A',
        'from_b'    => 'B',
    ];
    private static $cReference = [
        'inherited' => '',
        'from_a'    => 'A',
        'from_b'    => 'B',
    ];
    private static $dReference = [
        'inherited' => 'B',
        'from_a'    => 'A',
        'from_b'    => 'B',
    ];

    public function testInheritedConfig()
    {
        $a = new A();
        $this->validateConfig($a->getConfig(), self::$aReference, get_class($a));
        $b = new B();
        $this->validateConfig($b->getConfig(), self::$bReference, get_class($b));
        $c = new C();
        $this->validateConfig($c->getConfig(), self::$cReference, get_class($c));
        // Class Id for class D should be equal to B because D itself have no init / validate methods
        // @see Flying\Config\AbstractConfig::getConfigClassId
        $d = new D();
        $this->validateConfig($d->getConfig(), self::$dReference, get_class($b));
    }

    public function testInheritedModificationsA()
    {
        $a = new A();
        $a->setConfig([
            'inherited' => 'abc',
            'from_a'    => 'abc',
        ]);
        $this->validateConfig($a->getConfig(), [
            'inherited' => 'abc',
            'from_a'    => 'a',
        ], get_class($a));
    }

    public function testInheritedModificationsB()
    {
        $b = new B();
        $b->setConfig([
            'inherited' => 'abc',
            'from_a'    => 'abc',
            'from_b'    => 'abc',
        ]);
        $this->validateConfig($b->getConfig(), [
            'inherited' => 'abc',
            'from_a'    => 'a',
            'from_b'    => 'b',
        ], get_class($b));
    }

    public function testInheritedModificationsC()
    {
        $c = new C();
        $c->setConfig([
            'inherited' => 'abc',
            'from_a'    => 'abc',
            'from_b'    => 'abc',
        ]);
        $this->validateConfig($c->getConfig(), [
            'inherited' => 'c',
            'from_a'    => 'a',
            'from_b'    => 'b',
        ], get_class(new C()));
    }

    public function testOnConfigChangeCallback()
    {
        $object = new A();
        $this->runOnConfigChangeCallbackTest($object, self::$aReference);
        $object = new B();
        $this->runOnConfigChangeCallbackTest($object, self::$bReference);
        $object = new C();
        $this->runOnConfigChangeCallbackTest($object, self::$cReference);
    }

    /**
     * Get configuration object to test
     *
     * @return A
     */
    protected function getConfigObject()
    {
        return new A();
    }
}

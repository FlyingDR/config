<?php

namespace Flying\Tests\Config;

use Flying\Tests\Config\Object\A;
use Flying\Tests\Config\Object\B;
use Flying\Tests\Config\Object\C;

/**
 * Test for configuration objects work with inheritance
 */
class ConfigInheritanceTest extends AbstractConfigTest
{
    protected $_aReference = array(
        'inherited' => 'A',
        'from_a'    => 'A',
    );
    protected $_bReference = array(
        'inherited' => 'B',
        'from_a'    => 'A',
        'from_b'    => 'B',
    );

    public function testInheritedConfig()
    {
        $a = new A();
        $this->validateConfig($a->getConfig(), $this->_aReference, get_class($a));
        $b = new B();
        $this->validateConfig($b->getConfig(), $this->_bReference, get_class($b));
        // Class Id for class C should be equal to B
        $c = new C();
        $this->validateConfig($c->getConfig(), $this->_bReference, get_class($b));
    }

    public function testInheritedModificationsA()
    {
        $a = new A();
        $a->setConfig(array(
            'inherited' => 'abc',
            'from_a'    => 'abc',
        ));
        $this->validateConfig($a->getConfig(), array(
            'inherited' => 'abc',
            'from_a'    => 'a',
        ), get_class($a));
    }

    public function testInheritedModificationsB()
    {
        $b = new B();
        $b->setConfig(array(
            'inherited' => 'abc',
            'from_a'    => 'abc',
            'from_b'    => 'abc',
        ));
        $this->validateConfig($b->getConfig(), array(
            'inherited' => 'abc',
            'from_a'    => 'a',
            'from_b'    => 'b',
        ), get_class($b));
    }

    public function testInheritedModificationsC()
    {
        $c = new C();
        $c->setConfig(array(
            'inherited' => 'abc',
            'from_a'    => 'abc',
            'from_b'    => 'abc',
        ));
        $this->validateConfig($c->getConfig(), array(
            'inherited' => 'c',
            'from_a'    => 'a',
            'from_b'    => 'b',
        ), get_class(new B()));
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

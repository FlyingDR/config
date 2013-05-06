<?php

namespace Flying\Tests\Config\Object;

/**
 * Test class that implements toArray() method and can be passed to configuration class
 */
class ConfigAsToArrayObject
{
    private $properties = array();

    public function __construct(array $properties = array())
    {
        $this->properties = $properties;
    }

    public function toArray()
    {
        return ($this->properties);
    }

}

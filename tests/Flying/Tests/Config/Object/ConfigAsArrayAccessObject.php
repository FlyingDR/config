<?php

namespace Flying\Tests\Config\Object;

/**
 * Test class that implements ArrayAccess interface
 * to allow test passing such objects as configuration
 */
class ConfigAsArrayAccessObject implements \ArrayAccess
{
    private $properties = array();

    public function __construct(array $properties = array())
    {
        $this->properties = $properties;
    }

    public function offsetExists($offset)
    {
        return (isset($this->properties[$offset]));
    }

    public function offsetGet($offset)
    {
        return ($this->properties[$offset]);
    }

    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

}

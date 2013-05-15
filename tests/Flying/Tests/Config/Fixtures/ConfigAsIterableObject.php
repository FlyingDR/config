<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Test class that can be passed as configuration
 */
class ConfigAsIterableObject implements \Iterator
{
    private $properties = array();

    public function __construct(array $properties = array())
    {
        $this->properties = $properties;
    }

    function rewind()
    {
        return reset($this->properties);
    }

    function current()
    {
        return current($this->properties);
    }

    function key()
    {
        return key($this->properties);
    }

    function next()
    {
        return next($this->properties);
    }

    function valid()
    {
        return key($this->properties) !== null;
    }

}

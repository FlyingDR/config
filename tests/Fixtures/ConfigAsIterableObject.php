<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Test class that can be passed as configuration
 */
class ConfigAsIterableObject implements \Iterator
{
    private $properties;

    public function __construct(array $properties = [])
    {
        $this->properties = $properties;
    }

    public function rewind()
    {
        return reset($this->properties);
    }

    public function current()
    {
        return current($this->properties);
    }

    public function key()
    {
        return key($this->properties);
    }

    public function next()
    {
        return next($this->properties);
    }

    public function valid()
    {
        return key($this->properties) !== null;
    }
}

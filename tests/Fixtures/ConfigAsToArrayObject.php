<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Test class that implements toArray() method and can be passed to configuration class
 */
class ConfigAsToArrayObject
{
    private $properties;

    public function __construct(array $properties = [])
    {
        $this->properties = $properties;
    }

    public function toArray()
    {
        return $this->properties;
    }
}

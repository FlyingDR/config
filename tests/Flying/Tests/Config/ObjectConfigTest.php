<?php

namespace Flying\Tests\Config;

use Flying\Tests\Config\Object\ConfigurableObject;

/**
 * Test for standalone configuration object
 */
class ObjectConfigTest extends BaseConfigTest
{

    /**
     * Get configuration object to test
     *
     * @return ConfigurableObject
     */
    protected function getConfigObject()
    {
        return new ConfigurableObject();
    }

}

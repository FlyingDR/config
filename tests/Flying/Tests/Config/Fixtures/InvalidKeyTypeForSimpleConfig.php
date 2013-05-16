<?php

namespace Flying\Tests\Config\Fixtures;

use Flying\Config\AbstractConfig;

/**
 * Fixture class to test exception raising on passing
 * invalid types of keys as configuration options
 */
class InvalidKeyTypeForSimpleConfig extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            null,
            true,
            false,
            12345,
            array(1, 2, 3),
            new \ArrayObject(),
        ));
    }

}

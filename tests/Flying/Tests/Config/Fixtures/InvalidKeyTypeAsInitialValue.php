<?php

namespace Flying\Tests\Config\Fixtures;

use Flying\Config\AbstractConfig;

/**
 * Fixture class to test exception raising on passing
 * invalid types as initial value for configuration options
 */
class InvalidKeyTypeAsInitialValue extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'invalid_array'  => array(1, 2, 3),
            'invalid_object' => new \ArrayObject(),
        ));
    }
}

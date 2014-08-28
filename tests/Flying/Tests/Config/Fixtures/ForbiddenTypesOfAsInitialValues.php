<?php

namespace Flying\Tests\Config\Fixtures;

use Flying\Config\AbstractConfig;

/**
 * Fixture class to test exception raising on passing
 * invalid types as initial value for configuration options
 */
class ForbiddenTypesOfAsInitialValues extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'object_option' => new \ArrayObject(),
        ));
    }
}

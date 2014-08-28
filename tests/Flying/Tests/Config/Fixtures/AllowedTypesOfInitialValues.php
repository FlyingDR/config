<?php

namespace Flying\Tests\Config\Fixtures;

use Flying\Config\AbstractConfig;

class AllowedTypesOfInitialValues extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'boolean_option' => true,
            'string_option'  => 'some value',
            'int_option'     => 42,
            'float_option'   => 1.234,
            'array_option'   => array(true, 'some value', 42, 1.234),
        ));
    }
}

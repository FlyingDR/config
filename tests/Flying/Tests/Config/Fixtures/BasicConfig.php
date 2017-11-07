<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Basic fixture class
 */
class BasicConfig extends TestConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig([
            'string_option'  => 'some value',
            'boolean_option' => true,
            'int_option'     => 42,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfig($name, &$value)
    {
        switch ($name) {
            case 'string_option':
                $value = trim($value);
                break;
            case 'boolean_option':
                $value = (boolean)$value;
                break;
            case 'int_option':
                $value = (int)$value;
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
        return true;
    }
}

<?php

namespace Flying\Tests\Config\Fixtures;

use Flying\Config\AbstractConfig;

/**
 * Test class
 */
class BasicConfig extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'string_option'  => 'some value',
            'boolean_option' => true,
            'int_option'     => 42,
            'rejected'       => 'abc',
            'exception'      => null,
        ));
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
            case 'rejected':
                return false;
                break;
            case 'exception':
                throw new \Exception('Test exception on checking config option');
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
        return true;
    }
}

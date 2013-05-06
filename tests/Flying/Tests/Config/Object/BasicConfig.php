<?php

namespace Flying\Tests\Config\Object;

use Flying\Config\AbstractConfig;

/**
 * Test class
 */
class BasicConfig extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
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
    protected function _checkConfig($name, &$value, $operation)
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
                return parent::_checkConfig($name, $value, $operation);
                break;
        }
        return true;
    }
}

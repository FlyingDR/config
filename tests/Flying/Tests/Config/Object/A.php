<?php

namespace Flying\Tests\Config\Object;

use Flying\Config\AbstractConfig;

/**
 * Class A for testing configuration options inheritance
 */
class A extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'inherited' => 'A',
            'from_a'    => 'A',
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function _checkConfig($name, &$value, $operation)
    {
        switch ($name) {
            case 'inherited':
                $value = trim($value);
                break;
            case 'from_a':
                $value = preg_replace('/[^A]+/i', '', $value);
                break;
            default:
                return parent::_checkConfig($name, $value, $operation);
                break;
        }
        return true;
    }

}

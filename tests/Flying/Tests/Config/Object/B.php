<?php

namespace Flying\Tests\Config\Object;

/**
 * Class B for testing configuration options inheritance
 */
class B extends A
{
    /**
     * {@inheritdoc}
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'inherited' => 'B',
            'from_b'    => 'B',
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
            case 'from_b':
                $value = preg_replace('/[^B]+/i', '', $value);
                break;
            default:
                return parent::_checkConfig($name, $value, $operation);
                break;
        }
        return true;
    }

}

<?php

namespace Flying\Tests\Config\Object;

/**
 * Class C for testing configuration inheritance
 */
class C extends B
{

    /**
     * {@inheritdoc}
     */
    protected function _checkConfig($name, &$value, $operation)
    {
        switch ($name) {
            case 'inherited':
                $value = preg_replace('/[^C]+/i', '', $value);
                break;
            default:
                return parent::_checkConfig($name, $value, $operation);
                break;
        }
        return true;
    }

}

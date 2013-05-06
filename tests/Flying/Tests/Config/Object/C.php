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
    protected function validateConfig($name, &$value)
    {
        switch ($name) {
            case 'inherited':
                $value = preg_replace('/[^C]+/i', '', $value);
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
        return true;
    }

}

<?php

namespace Flying\Tests\Config\Fixtures;

use Flying\Config\AbstractConfig;

/**
 * Class A for testing configuration options inheritance
 */
class A extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'inherited' => 'A',
            'from_a'    => 'A',
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfig($name, &$value)
    {
        switch ($name) {
            case 'inherited':
                $value = trim($value);
                break;
            case 'from_a':
                $value = preg_replace('/[^A]+/i', '', $value);
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
        return true;
    }

}

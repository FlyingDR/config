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
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'inherited' => 'B',
            'from_b'    => 'B',
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfig($name, &$value, $operation)
    {
        switch ($name) {
            case 'inherited':
                $value = trim($value);
                break;
            case 'from_b':
                $value = preg_replace('/[^B]+/i', '', $value);
                break;
            default:
                return parent::validateConfig($name, $value, $operation);
                break;
        }
        return true;
    }

}

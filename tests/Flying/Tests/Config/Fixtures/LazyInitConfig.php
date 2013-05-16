<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Fixture class to test lazy initialization of configuration options
 */
class LazyInitConfig extends TestConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'string_option',
            'boolean_option',
            'int_option',
            'rejected',
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function lazyConfigInit($name)
    {
        $this->logCallbackCall(__FUNCTION__, func_get_args());
        switch ($name) {
            case 'string_option':
                return 'some value';
                break;
            case 'boolean_option':
                return true;
                break;
            case 'int_option':
                return 42;
                break;
            case 'rejected':
                return 'abc';
                break;
            default:
                return parent::lazyConfigInit($name);
                break;
        }
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
            default:
                return parent::validateConfig($name, $value);
                break;
        }
        return true;
    }

}

<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Fixture class to test attempt to pass invalid configuration option
 * for lazy initialization
 */
class LazyInitConfigWithInvalidValues extends LazyInitConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'should_be_boolean',
            'should_be_int',
            'should_be_string',
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function lazyConfigInit($name)
    {
        $this->logCallbackCall(__FUNCTION__, func_get_args());
        switch ($name) {
            case 'should_be_boolean':
                return 'abc';
                break;
            case 'should_be_int':
                return 123.45;
                break;
            case 'should_be_string':
                return 12345;
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
            case 'should_be_boolean':
                $value = (boolean)$value;
                break;
            case 'should_be_int':
                $value = (int)$value;
                break;
            case 'should_be_string':
                $value = (string)$value;
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
        return true;
    }

}

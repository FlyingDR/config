<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Fixture class to test attempt to pass invalid configuration option
 * for lazy initialization
 */
class LazyInitConfigWithRejectedValidation extends LazyInitConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig([
            'invalid_lazy_init',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function lazyConfigInit($name)
    {
        $this->logCallbackCall(__FUNCTION__, func_get_args());
        /** @noinspection DegradedSwitchInspection */
        switch ($name) {
            case 'invalid_lazy_init':
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
        /** @noinspection DegradedSwitchInspection */
        switch ($name) {
            case 'invalid_lazy_init':
                return false;
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
    }
}

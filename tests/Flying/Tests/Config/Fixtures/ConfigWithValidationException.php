<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Fixture class to test "exception in validation" behavior
 */
class ConfigWithValidationException extends BasicConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'exception' => null,
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfig($name, &$value)
    {
        switch ($name) {
            case 'exception':
                throw new \Exception('Test exception on checking config option');
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
    }

}

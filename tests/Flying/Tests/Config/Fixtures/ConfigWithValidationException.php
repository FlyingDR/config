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
        $this->mergeConfig([
            'exception' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfig($name, &$value)
    {
        /** @noinspection DegradedSwitchInspection */
        switch ($name) {
            case 'exception':
                throw new \RuntimeException('Test exception on checking config option');
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
    }
}

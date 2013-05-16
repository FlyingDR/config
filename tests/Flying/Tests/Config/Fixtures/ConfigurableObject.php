<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Standalone configurable object
 */
class ConfigurableObject extends BaseConfigurableObject
{

    /**
     * Get configuration options for test configuration object
     *
     * @return array
     */
    protected function getConfigOptions()
    {
        return array(
            'string_option'  => 'some value',
            'boolean_option' => true,
            'int_option'     => 42,
            'rejected'       => 'abc',
            'exception'      => null,
        );
    }

    /**
     * Get callbacks for test configuration object
     *
     * @return array
     * @throws \Exception
     */
    protected function getConfigCallbacks()
    {
        return array(
            'validateConfig' => function ($name, &$value) {
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
                    case 'exception':
                        throw new \Exception('Test exception on checking config option');
                        break;
                }
                return true;
            },
            'onConfigChange' => function ($name, $value, $merge) {
                $this->logCallbackCall('onConfigChange', func_get_args());
            },
        );
    }

}

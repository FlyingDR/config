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
            'string_option',
            'boolean_option',
            'int_option',
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
            'validateConfig' => array($this, 'cbValidateConfig'),
            'onConfigChange' => array($this, 'cbOnConfigChange'),
            'lazyConfigInit' => array($this, 'cbLazyConfigInit'),
        );
    }

    public function cbValidateConfig($name, &$value)
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
        }
        return true;
    }

    public function cbOnConfigChange($name, $value)
    {
        $this->logCallbackCall('onConfigChange', func_get_args());
    }

    public function cbLazyConfigInit($name)
    {
        $this->logCallbackCall('lazyConfigInit', func_get_args());
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
            default:
                return null;
                break;
        }
    }

}

<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Fixture class to test "modification rejected" behavior
 */
class ConfigWithRejectedValidation extends BasicConfig
{
    /**
     * "Modification rejected" status
     *
     * @var boolean
     */
    protected $_reject = true;

    /**
     * @param bool $reject
     */
    public function __construct($reject = false)
    {
        // Avoid rejecting validation on configuration bootstrap
        // because mergeConfig() throws exception on invalid initial values
        $this->setReject($reject);
        $this->bootstrapConfig();
        $this->setReject(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'rejected' => 'abc',
        ));
    }

    /**
     * Set status of "modification rejected" behavior
     *
     * @param boolean $status
     * @return void
     */
    public function setReject($status)
    {
        $this->_reject = (boolean)$status;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfig($name, &$value)
    {
        switch ($name) {
            case 'rejected':
                return !$this->_reject;
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
    }
}

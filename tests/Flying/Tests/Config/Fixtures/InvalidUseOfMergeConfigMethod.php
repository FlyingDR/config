<?php

namespace Flying\Tests\Config\Fixtures;

/**
 * Fixture class to test exception raising
 * on calling mergeConfig() outside object initialization process
 */
class InvalidUseOfMergeConfigMethod extends BasicConfig
{
    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'test' => 123,
        ));
    }

    public function callMergeConfig()
    {
        $this->mergeConfig(array(
            'another' => 1234,
        ));
    }
}

<?php

namespace Flying\Tests\Config;

use Flying\Config\ConfigurableInterface;
use Flying\Tests\Config\Fixtures\BasicConfig;
use Flying\Tests\Config\Fixtures\ConfigAsIterableObject;
use Flying\Tests\Config\Fixtures\ConfigWithRejectedValidation;
use Flying\Tests\Config\Fixtures\ConfigWithValidationException;
use Flying\Tests\Config\Fixtures\InvalidKeyTypeAsInitialValue;
use Flying\Tests\Config\Fixtures\InvalidKeyTypeForSimpleConfig;
use Flying\Tests\Config\Fixtures\InvalidUseOfMergeConfigMethod;

class BaseConfigTest extends AbstractConfigTest
{
    /**
     * List of objects that can be used to pass config modifications to object
     *
     * @var array
     */
    protected $_configObjects = array(
        'ConfigAsArrayAccessObject',
        'ConfigAsIterableObject',
        'ConfigAsToArrayObject',
    );
    protected $_configReference = array(
        'string_option'  => 'some value',
        'boolean_option' => true,
        'int_option'     => 42,
    );
    protected $_configModifications = array(
        'string_option'  => 'modified value',
        'boolean_option' => false,
        'int_option'     => 12345,
    );
    protected $_configModificationReference = array(
        'string_option'  => 'modified value',
        'boolean_option' => false,
        'int_option'     => 12345,
    );
    protected $_configSetModifications = array(
        'string_option'  => 12345,
        'boolean_option' => 12345,
        'int_option'     => 12345,
    );
    protected $_configSetReference = array(
        'string_option'  => '12345',
        'boolean_option' => true,
        'int_option'     => 12345,
    );

    public function testExistenceChecks()
    {
        $object = $this->getConfigObject();
        $this->assertTrue($object->isConfigExists('string_option'));
        $this->assertTrue($object->isConfigExists('boolean_option'));
        $this->assertTrue($object->isConfigExists('int_option'));
        $this->assertFalse($object->isConfigExists('nonexistent_option'));
        // Test passing invalid argument type for method
        $this->assertFalse($object->isConfigExists(array('int_option')));
        $this->assertFalse($object->isConfigExists(null));
        $this->assertFalse($object->isConfigExists(true));
        $this->assertFalse($object->isConfigExists(array()));
        $this->assertFalse($object->isConfigExists(new \ArrayObject()));
        // Class Id should not be visible
        $this->assertFalse($object->isConfigExists(ConfigurableInterface::CLASS_ID_KEY));
    }

    public function testGettingSingleValues()
    {
        $object = $this->getConfigObject();
        $this->assertEquals($object->getConfig('string_option'), 'some value');
        $this->assertTrue($object->getConfig('boolean_option'));
        $this->assertEquals($object->getConfig('int_option'), 42);
        $this->assertNull($object->getConfig('nonexistent_option'));
    }

    public function testGettingCompleteConfig()
    {
        $object = $this->getConfigObject();
        $this->validateConfig($object->getConfig(), $this->_configReference);
    }

    public function testRepetitiveConfigGetting()
    {
        $object = $this->getConfigObject();
        $c1 = $object->getConfig();
        $c2 = $object->getConfig($c1);
        $this->assertTrue($c1 === $c2);
    }

    public function testPassingInvalidValuesAsConfigModifications()
    {
        $object = $this->getConfigObject();
        $this->validateConfig($object->getConfig(null), $this->_configReference);
        $this->validateConfig($object->getConfig(true), $this->_configReference);
        $this->validateConfig($object->getConfig(false), $this->_configReference);
        $this->validateConfig($object->getConfig(array()), $this->_configReference);
        $this->validateConfig($object->getConfig(new \ArrayObject()), $this->_configReference);
    }

    public function testGettingConfigWithModifications()
    {
        $this->runConfigModificationTest($this->_configModifications, $this->_configModificationReference);
    }

    public function testPassingObjectsAsConfigModifications()
    {
        foreach ($this->_configObjects as $configObject) {
            $configObject = join('\\', array(__NAMESPACE__, 'Fixtures', $configObject));
            $modifications = new $configObject($this->_configModifications);
            $this->runConfigModificationTest($modifications, $this->_configModificationReference);
        }
    }

    public function testConfigWithModificationsShouldNotIncludeNonExistentProperties()
    {
        $modifications = $this->_configModifications;
        $modifications['nonexisting'] = 'option';
        $this->runConfigModificationTest($modifications, $this->_configModificationReference);
    }

    protected function runConfigModificationTest($modifications, $reference)
    {
        $object = $this->getConfigObject();
        $config = $object->getConfig($modifications);
        $this->validateConfig($config, $reference);
    }

    public function testSettingSingleConfigOption()
    {
        $object = $this->getConfigObject();

        // Test setting normal configuration options
        $object->setConfig('string_option', 'another value');
        $this->assertEquals($object->getConfig('string_option'), 'another value');
        $object->setConfig('string_option', 12345);
        $this->assertTrue($object->getConfig('string_option') === '12345');
        $this->assertFalse($object->getConfig('string_option') === 12345);
        $object->setConfig('boolean_option', 123);
        $this->assertTrue($object->getConfig('boolean_option'));
        $object->setConfig('boolean_option', 0);
        $this->assertFalse($object->getConfig('boolean_option'));
        $object->setConfig('int_option', 12345);
        $this->assertTrue($object->getConfig('int_option') === 12345);
        $this->assertFalse($object->getConfig('int_option') === '12345');
        $object->setConfig('int_option', 123.45);
        $this->assertTrue($object->getConfig('int_option') === 123);
        $this->assertFalse($object->getConfig('int_option') === 123.45);
    }

    public function testSettingInvalidConfigOptions()
    {
        // Passing invalid names of configuration options should not affect object configuration
        $invalidValues = array(
            null,
            true,
            false,
            'some nonexisting key',
            array(),
            new \ArrayObject(),
        );
        foreach ($invalidValues as $value) {
            $object = $this->getConfigObject();
            $object->setConfig($value);
            $this->validateConfig($object->getConfig(), $this->_configReference);
        }
    }

    public function testSettingRejectedConfigOption()
    {
        $object = new ConfigWithRejectedValidation();
        $object->setConfig('rejected', 'xyz');
        $this->assertEquals($object->getConfig('rejected'), 'abc');
    }

    public function testExceptionDuringValidation()
    {
        $object = new ConfigWithValidationException();
        $this->setExpectedException('\Exception', 'Test exception on checking config option');
        $object->setConfig('exception', 'abc');
    }

    public function testSettingMultipleConfigOptions()
    {
        $object = $this->getConfigObject();
        $object->setConfig($this->_configSetModifications);
        $this->validateConfig($object->getConfig(), $this->_configSetReference);
    }

    public function testPassingObjectsAsConfigSetters()
    {
        foreach ($this->_configObjects as $configObject) {
            $configObject = join('\\', array(__NAMESPACE__, 'Fixtures', $configObject));
            $modifications = new $configObject($this->_configSetModifications);
            $object = $this->getConfigObject();
            $object->setConfig($modifications);
            $this->validateConfig($object->getConfig(), $this->_configSetReference);
        }
    }

    public function testConfigModifications()
    {
        $object = $this->getConfigObject();
        $config = $object->getConfig();

        // Test modification of single config option
        $modified = $object->modifyConfig($config, 'int_option', 12345);
        $this->validateConfig($modified, array(
            'string_option'  => 'some value',
            'boolean_option' => true,
            'int_option'     => 12345,
        ));
        // Make sure that we didn't modify value of original configuration option
        $this->assertEquals($object->getConfig('int_option'), 42);

        // Test modification of multiple config options at once
        $modified = $object->modifyConfig($config, array(
            'string_option'  => 'another value',
            'boolean_option' => false,
        ));
        $this->validateConfig($modified, array(
            'string_option'  => 'another value',
            'boolean_option' => false,
            'int_option'     => 42,
        ));

        // Test modification of multiple config options using object
        $modified = $object->modifyConfig($config, new ConfigAsIterableObject($this->_configModifications));
        $this->validateConfig($modified, $this->_configModificationReference);

        // Make sure that we didn't modify value of original configuration options
        $this->validateConfig($object->getConfig(), $this->_configReference);
    }

    public function testModificationsWithRejectedValidation()
    {
        $object = new ConfigWithRejectedValidation();
        $config = $object->getConfig();
        $modified = $object->modifyConfig($config, array(
            'string_option'  => 'another value',
            'boolean_option' => false,
            'rejected'       => 'xyz',
        ));
        $this->validateConfig($modified, array(
            'string_option'  => 'another value',
            'boolean_option' => false,
            'int_option'     => 42,
            'rejected'       => 'abc',
        ), get_class($object));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidInitialConfigurationOptionResultsInException()
    {
        new ConfigWithRejectedValidation(true);
    }

    public function testConfigIdEqualsClassId()
    {
        $object = $this->getConfigObject();
        $config = $object->getConfig();
        $this->assertEquals($config[ConfigurableInterface::CLASS_ID_KEY], get_class($this->getConfigObject()));
    }

    public function testClassIdShouldBeImmutable()
    {
        $object = $this->getConfigObject();

        $idKey = ConfigurableInterface::CLASS_ID_KEY;
        $classId = get_class($this->getConfigObject());

        // Set explicitly
        $object->setConfig($idKey, 'something');
        $config = $object->getConfig();
        $this->assertEquals($config[$idKey], $classId);

        // Set as part of multi-element set
        $object->setConfig(array(
            $idKey       => 'something',
            'int_option' => 12345,
        ));
        $config = $object->getConfig();
        $this->assertEquals($config[$idKey], $classId);

        // Get with modifications
        $config = $object->getConfig(array(
            $idKey       => 'something',
            'int_option' => 12345,
        ));
        $this->assertEquals($config[$idKey], $classId);

        // Modify configuration
        $config = $object->modifyConfig(array(
            $idKey       => 'something',
            'int_option' => 12345,
        ), 'boolean_value', false);
        $this->assertEquals($config[$idKey], $classId);
    }

    public function testOnConfigChangeCallback()
    {
        $tests = $this->_configModifications;
        $this->runOnConfigChangeCallbackTest($this->getConfigObject(), $tests);
    }

    public function testInvalidKeyTypeForSimpleConfigDeclaration()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Configuration option name must be a string');
        $object = new InvalidKeyTypeForSimpleConfig();
        $object->getConfig();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidKeyTypeAsInitialValueRaisesException()
    {
        $object = new InvalidKeyTypeAsInitialValue();
        $object->getConfig();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMergeConfigMethodCantBeUsedOutsideInitialization()
    {
        $object = new InvalidUseOfMergeConfigMethod();
        $object->callMergeConfig();
    }

    /**
     * Get configuration object to test
     *
     * @return BasicConfig
     */
    protected function getConfigObject()
    {
        return new BasicConfig();
    }

}

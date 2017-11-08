<?php

namespace Flying\Tests\Config;

use Flying\Config\ConfigurableInterface;
use Flying\Tests\Config\Fixtures\AllowedTypesOfInitialValues;
use Flying\Tests\Config\Fixtures\BasicConfig;
use Flying\Tests\Config\Fixtures\ConfigAsIterableObject;
use Flying\Tests\Config\Fixtures\ConfigWithRejectedValidation;
use Flying\Tests\Config\Fixtures\ConfigWithValidationException;
use Flying\Tests\Config\Fixtures\ForbiddenTypesOfAsInitialValues;
use Flying\Tests\Config\Fixtures\InvalidKeyTypeForSimpleConfig;
use Flying\Tests\Config\Fixtures\InvalidUseOfMergeConfigMethod;

class BaseConfigTest extends AbstractConfigTest
{
    /**
     * List of objects that can be used to pass config modifications to object
     *
     * @var array
     */
    private static $configObjects = [
        'ConfigAsArrayAccessObject',
        'ConfigAsIterableObject',
        'ConfigAsToArrayObject',
    ];
    private static $configReference = [
        'string_option'  => 'some value',
        'boolean_option' => true,
        'int_option'     => 42,
    ];
    private static $configModifications = [
        'string_option'  => 'modified value',
        'boolean_option' => false,
        'int_option'     => 12345,
    ];
    private static $configModificationReference = [
        'string_option'  => 'modified value',
        'boolean_option' => false,
        'int_option'     => 12345,
    ];
    private static $configSetModifications = [
        'string_option'  => 12345,
        'boolean_option' => 12345,
        'int_option'     => 12345,
    ];
    private static $configSetReference = [
        'string_option'  => '12345',
        'boolean_option' => true,
        'int_option'     => 12345,
    ];

    public function testExistenceChecks()
    {
        $object = $this->getConfigObject();
        $this->assertTrue($object->isConfigExists('string_option'));
        $this->assertTrue($object->isConfigExists('boolean_option'));
        $this->assertTrue($object->isConfigExists('int_option'));
        $this->assertFalse($object->isConfigExists('nonexistent_option'));
        // Test passing invalid argument type for method
        /** @noinspection PhpParamsInspection */
        $this->assertFalse($object->isConfigExists(['int_option']));
        $this->assertFalse($object->isConfigExists(null));
        $this->assertFalse($object->isConfigExists(true));
        /** @noinspection PhpParamsInspection */
        $this->assertFalse($object->isConfigExists([]));
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
        $this->validateConfig($object->getConfig(), self::$configReference);
    }

    public function testRepetitiveConfigGetting()
    {
        $object = $this->getConfigObject();
        $c1 = $object->getConfig();
        $c2 = $object->getConfig($c1);
        $this->assertSame($c1, $c2);
    }

    public function testGettingExportedConfig()
    {
        $object = $this->getConfigObject();

        $config = $object->getConfig();
        $this->assertArrayHasKey(ConfigurableInterface::CLASS_ID_KEY, $config);

        $modified = $object->getConfig(self::$configModifications);
        $this->assertArrayHasKey(ConfigurableInterface::CLASS_ID_KEY, $modified);
        $this->validateConfig($modified, self::$configModifications);

        $exported = $object->getConfig(null, true);
        $this->assertArrayNotHasKey(ConfigurableInterface::CLASS_ID_KEY, $exported);
        $this->assertEquals($exported, self::$configReference);

        $modified = $object->getConfig(self::$configModifications, true);
        $this->assertArrayNotHasKey(ConfigurableInterface::CLASS_ID_KEY, $modified);
        $this->assertEquals($modified, self::$configModifications);
    }

    public function testPassingInvalidValuesAsConfigModifications()
    {
        $object = $this->getConfigObject();
        $this->validateConfig($object->getConfig(), self::$configReference);
        $this->validateConfig($object->getConfig(true), self::$configReference);
        $this->validateConfig($object->getConfig(false), self::$configReference);
        $this->validateConfig($object->getConfig([]), self::$configReference);
        $this->validateConfig($object->getConfig(new \ArrayObject()), self::$configReference);
    }

    public function testGettingConfigWithModifications()
    {
        $this->runConfigModificationTest(self::$configModifications, self::$configModificationReference);
    }

    public function testPassingObjectsAsConfigModifications()
    {
        foreach (self::$configObjects as $configObject) {
            $configObject = implode('\\', [__NAMESPACE__, 'Fixtures', $configObject]);
            $modifications = new $configObject(self::$configModifications);
            $this->runConfigModificationTest($modifications, self::$configModificationReference);
        }
    }

    public function testConfigWithModificationsShouldNotIncludeNonExistentProperties()
    {
        $modifications = self::$configModifications;
        $modifications['nonexisting'] = 'option';
        $this->runConfigModificationTest($modifications, self::$configModificationReference);
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
        $this->assertSame($object->getConfig('string_option'), '12345');
        $this->assertNotSame($object->getConfig('string_option'), 12345);
        $object->setConfig('boolean_option', 123);
        $this->assertTrue($object->getConfig('boolean_option'));
        $object->setConfig('boolean_option', 0);
        $this->assertFalse($object->getConfig('boolean_option'));
        $object->setConfig('int_option', 12345);
        $this->assertSame($object->getConfig('int_option'), 12345);
        $this->assertNotSame($object->getConfig('int_option'), '12345');
        $object->setConfig('int_option', 123.45);
        $this->assertSame($object->getConfig('int_option'), 123);
        $this->assertNotSame($object->getConfig('int_option'), 123.45);
    }

    public function testSettingInvalidConfigOptions()
    {
        // Passing invalid names of configuration options should not affect object configuration
        $invalidValues = [
            null,
            true,
            false,
            'some nonexisting key',
            [],
            new \ArrayObject(),
        ];
        foreach ($invalidValues as $value) {
            $object = $this->getConfigObject();
            $object->setConfig($value);
            $this->validateConfig($object->getConfig(), self::$configReference);
        }
    }

    public function testSettingRejectedConfigOption()
    {
        $object = new ConfigWithRejectedValidation();
        $object->setConfig('rejected', 'xyz');
        $this->assertEquals($object->getConfig('rejected'), 'abc');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Test exception on checking config option
     */
    public function testExceptionDuringValidation()
    {
        $object = new ConfigWithValidationException();
        $object->setConfig('exception', 'abc');
    }

    public function testSettingMultipleConfigOptions()
    {
        $object = $this->getConfigObject();
        $object->setConfig(self::$configSetModifications);
        $this->validateConfig($object->getConfig(), self::$configSetReference);
    }

    public function testPassingObjectsAsConfigSetters()
    {
        foreach (self::$configObjects as $configObject) {
            $configObject = implode('\\', [__NAMESPACE__, 'Fixtures', $configObject]);
            $modifications = new $configObject(self::$configSetModifications);
            $object = $this->getConfigObject();
            $object->setConfig($modifications);
            $this->validateConfig($object->getConfig(), self::$configSetReference);
        }
    }

    public function testConfigModifications()
    {
        $object = $this->getConfigObject();
        $config = $object->getConfig();

        // Test modification of single config option
        $modified = $object->modifyConfig($config, 'int_option', 12345);
        $this->validateConfig($modified, [
            'string_option'  => 'some value',
            'boolean_option' => true,
            'int_option'     => 12345,
        ]);
        // Make sure that we didn't modify value of original configuration option
        $this->assertEquals($object->getConfig('int_option'), 42);

        // Test modification of multiple config options at once
        $modified = $object->modifyConfig($config, [
            'string_option'  => 'another value',
            'boolean_option' => false,
        ]);
        $this->validateConfig($modified, [
            'string_option'  => 'another value',
            'boolean_option' => false,
            'int_option'     => 42,
        ]);

        // Test modification of multiple config options using object
        $modified = $object->modifyConfig($config, new ConfigAsIterableObject(self::$configModifications));
        $this->validateConfig($modified, self::$configModificationReference);

        // Make sure that we didn't modify value of original configuration options
        $this->validateConfig($object->getConfig(), self::$configReference);
    }

    public function testModificationsWithRejectedValidation()
    {
        $object = new ConfigWithRejectedValidation();
        $config = $object->getConfig();
        $modified = $object->modifyConfig($config, [
            'string_option'  => 'another value',
            'boolean_option' => false,
            'rejected'       => 'xyz',
        ]);
        $this->validateConfig($modified, [
            'string_option'  => 'another value',
            'boolean_option' => false,
            'int_option'     => 42,
            'rejected'       => 'abc',
        ], get_class($object));
    }

    public function testEmptyModification()
    {
        $object = $this->getConfigObject();
        $config = $object->getConfig();
        $modified = $object->modifyConfig($config, null, 123);
        $this->assertEquals($config, $modified);
    }

    public function testModificationWithUnavailableOptions()
    {
        $object = $this->getConfigObject();
        $config = $object->getConfig();
        $modified = $object->modifyConfig($config, [
            'abc' => 123,
            'xyz' => 456,
        ]);
        $this->assertEquals($config, $modified);
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
        $object->setConfig([
            $idKey       => 'something',
            'int_option' => 12345,
        ]);
        $config = $object->getConfig();
        $this->assertEquals($config[$idKey], $classId);

        // Get with modifications
        $config = $object->getConfig([
            $idKey       => 'something',
            'int_option' => 12345,
        ]);
        $this->assertEquals($config[$idKey], $classId);

        // Modify configuration
        $config = $object->modifyConfig([
            $idKey       => 'something',
            'int_option' => 12345,
        ], 'boolean_value', false);
        $this->assertEquals($config[$idKey], $classId);
    }

    public function testOnConfigChangeCallback()
    {
        $tests = self::$configModifications;
        $this->runOnConfigChangeCallbackTest($this->getConfigObject(), $tests);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Configuration option name must be a string
     */
    public function testInvalidKeyTypeForSimpleConfigDeclaration()
    {
        $object = new InvalidKeyTypeForSimpleConfig();
        $object->getConfig();
    }

    public function testAllowedTypesOfInitialValues()
    {
        $object = new AllowedTypesOfInitialValues();
        $object->getConfig();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testForbiddenTypesOfAsInitialValuesRaisesException()
    {
        $object = new ForbiddenTypesOfAsInitialValues();
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

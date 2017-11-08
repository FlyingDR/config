Object configuration implementation
===================================
[![Build Status](https://travis-ci.org/FlyingDR/config.svg?branch=master)](https://travis-ci.org/FlyingDR/config) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FlyingDR/config/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/FlyingDR/config/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/FlyingDR/config/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/FlyingDR/config/?branch=master)
# Overview
This package provides simple, fast and flexible implementation of object's configuration management.

It is pretty common task for objects to have some configuration options that somehow affects their behavior. Usually this functionality is implemented:
* As a set of configurable properties with either corresponding getter/setter pairs for each property ([example](https://github.com/zendframework/zf2/blob/release-2.1.5/library/Zend/Uri/Uri.php))
* Array with [configuration options](https://github.com/zendframework/zf2/blob/release-2.1.5/library/Zend/Validator/AbstractValidator.php#L48) and corresponding [accessors](https://github.com/zendframework/zf2/blob/release-2.1.5/library/Zend/Validator/AbstractValidator.php#L96)
* Use of external [configuration class](https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/Configuration.php)

Or as some kind of mix of these ways.

First way is most common, however it have some drawbacks:
* It is hard to pass changes to multiple properties between objects
* It is hard to implement temporary changes to object's configuration for single method call

This package implements second of described ways of objects configuration - through accessors to object's configuration options.

# Highlights
* Arbitrary configuration options are supported, defined by class
* Configuration values are always validated before use to make sure that configuration is always valid
* Expansion of given partial configuration is fully supported
* Full support for configuration inheritance and extending into nested classes
* Implemented both as abstract base class to inherit from and as standalone class to use separately
* Hook for implementing custom logic upon configuration change is provided

# Basic usage
Package provides two different ways to use its functionality - base class to inherit your classes from and standalone implementation to use in arbitrary classes.

## Use of base class implementation
Minimum requirement to use base class implementation - is definition of class configuration options.
### Configuration options definition
Configuration options definition is implemented by overloading ```initConfig()``` method. Overloading should be done in a way described in method itself to make sure that configuration inheritance will work properly.

Let's say we're configuring some kind of file-based cache provider:

```php
use Flying\Config\AbstractConfig;

class MyCache extends AbstractConfig
{
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig([
            'enabled'           => true,    // Boolean value
            'path'              => null,    // Path to directory where to store files, no path is configured by default
            'prefix'            => null,    // Prefix for cache entries (string or null)
            'hash_method'       => 'md5',   // Hash method to use for cache keys generation, accepted values are 'md5' and 'sha1'
            'directory_depth'   => 1,       // Depth of hashed directories structure (integer greater then 0)
            'lifetime'          => 3600,    // Cache entry lifetime in seconds
        ]);
    }
}
```

At this point we're ready to use defined configuration options. We can get some configuration values:
```php
if ($this->getConfig('enabled')) {
    // Cache is enabled, calculate hash of cache key
    $method = $this->getConfig('hash_method');
    $hash = $method($key);
    // Proceed with cache entry ...
}
```
or set them one by one
```php
$cache->setConfig('enabled', true);
```
or multiple values at once
```php
$cache->setConfig([
    'path'      => realpath(__DIR__ . '/../../cache'),
    'prefix'    => 'my_',
]);
```
but at this point we can't be sure that our configuration is valid at any point of time. To achieve this - it is necessary to implement configuration validator.

**IMPORTANT**: Starting from version 2.0.0 only scalar and array configuration options can be passed to ```initConfig()``` because configuration initialization results are now cached for better performance. Passing any kind of dynamic values (e.g. array of objects) to ```initConfig()``` may cause unwanted side effects, but not controlled for performance reasons.

### Configuration validation
To make configuration valid - we need to implement one more method: ```validateConfig()```. This method receives name and value of configuration option and should decide if configuration option can be changed in this way and, optionally, can normalize given value. For example validator for our example configuration may look like this:
```php
protected function validateConfig($name, &$value)
{
    switch ($name) {
        case 'enabled':
            $value = (boolean)$value;
            break;
        case 'path':
            if ($value !== null) {
                // Check if path is available
                if (!is_dir($value)) {
                    throw new \InvalidArgumentException('Unavailable path to cache directory: ' . $value);
                }
            }
            break;
        case 'prefix':
            if (strlen($value)) {
                $value = rtrim($value, '_') . '_';
            } else {
                $value = null;
            }
            break;
        case 'hash_method':
            if (is_string($value)) {
                if (!in_array($value, ['md5', 'sha1'])) {
                    trigger_error('Invalid hash method: ' . $value, E_USER_WARNING);
                    return false;
                }
            } else {
                trigger_error('Hash method must be a string', E_USER_WARNING);
                return false;
            }
            break;
        case 'directory_depth':
            $value = max(min((int)$value, 3), 1);
            break;
        case 'lifetime':
            if ($value!==null) {
                $value = max(min((int)$value, 86400), 1);
            }
            break;
        default:
            return parent::validateConfig($name, $value);
            break;
    }
    return true;
}
```
After implementing this method we can be sure that our configuration will be valid at any time.
### Lazy configuration initialization
As of v1.1.0 it is possible to perform lazy (upon request) initialization of configuration options. It is especially helpful in a case if configuration options contains some information that is expensive to initialize by default, e.g. object instances. To initialize some configuration option lazily you need to set its default value to ```null``` and implement option initialization into ```lazyConfigInit()``` method. For example:
```php
protected function initConfig()
{
    parent::initConfig();
    $this->mergeConfig([
        'cache'     => null,    // Cache object instance will be here
    ]);
}

protected function lazyConfigInit($name)
{
    switch($name) {
        case 'cache':
            return new My\Cache();
            break;
        default:
            return parent::lazyConfigInit($name);
            break;
    }
}
```
and later in code:
```php
// Create instance of object with 'cache' configuration option
$object = new My\Object();
// 'cache' option now remains null internally
$cache = $object->getConfig('cache');
// $cache contains instance of My\Cache initialized by request
$cache->save();
```
If your object configuration is planned to be **completely** initialized in a lazy way - you can simplify your configuration initalization:
```php
protected function initConfig()
{
    parent::initConfig();
    $this->mergeConfig([
        'cache',        // No values are required
        'loader',
        'some_service',
    ]);
}
```
# Partial configuration expansion
Sometimes it may be necessary to run some object's method with different configuration. This can be done by passing additional configuration options to method itself as additional argument. But in general case we can't be sure that given set of configuration options is complete (and not just 1-2 options) and we know nothing about its validity. This package have great support for handling such situations. Let's take a look at example:
```php
/**
 * Save cache entry
 *
 * @param string $key       Cache entry key
 * @param mixed $contents   Cache entry contents
 * @param array $config     OPTIONAL Additional configuration options
 * @return boolean
 */
public function save($key, $contents, $config = null)
{
    // At this moment we can't tell anything about contents of $config argument
    $config = $this->getConfig($config);
    // And now we can be sure that $config stores complete set
    // of object's configuration options with valid values!
    // We can safely keep going with logic of this method ...

}
```
You can see how simple it is. With just one line of code we got:
* Complete set of object's configuration options
* Ensure that all configuration options in this variable are valid
* Ensure that all given modifications to configuration options are validated and applied

And actual object's configuration is *not* modified, so we can work with our local, possibly modified copy of configuration while keeping actual configuration pristine.
### Configuration modifications
Sometimes it may be necessary to modify our local copy of object's configuration and do it in a safe way to be sure that our configuration is still complete and valid. It can be achieved by using ```modifyConfig()``` method. It accepts configuration options array, applies given modifications to it and returns resulted configuration.

### Various configuration sources
Sometimes it may be necessary to import object's configuration from another object. It can be done by passing such object (e.g. ```Zend\Config\Config``` or something like this) directly to ```setConfig()``` method.

### Configuration inheritance and extension
It is often required to extend list of configuration options into child classes. This functionality will be achieved automatically as long as ```initConfig()``` and ```validateConfig()``` methods will be implemented in a way described in these methods (or this document).

### Configuration change tracking
It is often necessary to perform some additional tasks upon change of object's configuration. It can be done by overriding ```onConfigChange()``` method. It is called each time after configuration option is changed. Name and new value of configuration option are given as arguments to this method. Good practice for implementation of this method would be to follow same structure as for ```validateConfig()``` method to ensure proper work of application's logic in a case of inherited classes.

## Use of standalone implementation
Standalone implementation is provided by [```Flying\Config\ObjectConfig```](https://github.com/FlyingDR/config/blob/master/lib/Flying/Config/ObjectConfig.php) class and provides same API and base implementation. Fully-functional usage example can be seen into corresponding [test class](https://github.com/FlyingDR/config/blob/master/tests/Flying/Tests/Config/Fixtures/BaseConfigurableObject.php).

It can be seen that it is generally may be good idea to implement [```Flying\Config\ConfigurableInterface```](https://github.com/FlyingDR/config/blob/master/lib/Flying/Config/ConfigurableInterface.php) for such objects and proxy all methods to internal configuration object. In this case your object with standalone version of configuration functionality will be functionally equal to objects inherited from [```Flying\Config\AbstractConfig```](https://github.com/FlyingDR/config/blob/master/lib/Flying/Config/AbstractConfig.php).

## Implementation details
For flexibility and performance reasons configuration options are stored and passed as arrays. To distinguish object's configuration from regular arrays - they have additional ```__config__``` entry (defined in [```Flying\Config\ConfigurableInterface```](https://github.com/FlyingDR/config/blob/master/lib/Flying/Config/ConfigurableInterface.php)).

Existence of this entry should be taken in mind when, for example, iterating over configuration options list:
```php
$config = $this->getConfig();
foreach($config as $key => $value) {
    if (\Flying\Config\ConfigurableInterface::CLASS_ID_KEY === $key) {
        continue;
    }
    // ...
}
```
If you need to avoid getting this additional entry - you can pass ```false``` as second argument to ```getConfig()``` method (only in 2.x version). However it is usually bad idea to drop this entry if you plan to pass this configuration options somewhere because it will cause configuration re-validation on next access that may cause small performance penalty.
Another decision made for performance reasons: arrays that contains configuration id key are automatically treated as valid and **not** re-validated. It is your obligation to **not modify** configuration arrays by hands, you need to use ```modifyConfig()``` method instead.

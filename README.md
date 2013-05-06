Object configuration functionality
==================================

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
Minimum requirement to use base class implementation - is definition of class configuration options by overloading ```initConfig()``` method. Overloading should be done in a way described in method itself to make sure that configuration inheritance will work properly.

Let's say we're configuring some kind of file-based cache provider:

```php
<?php

use Flying\Config\AbstractConfig;

class MyCache extends AbstractConfig
{

    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'enabled'           => true,    // Boolean value
            'path'              => null,    // Path to directory where to store files, no path is configured by default
            'prefix'            => null,    // Prefix for cache entries (string or null)
            'hash_method'       => 'md5',   // Hash method to use for cache keys generation, accepted values are 'md5' and 'sha1'
            'directory_depth'   => 1,       // Depth of hashed directories structure (integer greater then 0)
        ));
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
$cache->setConfig(array(
    'path'      => realpath(__DIR__ . '/../../cache'),
    'prefix'    => 'my_',
));
```
but at this point we can't be sure that our configuration is valid at any point of time. To achieve this - it is necessary to implement one more method: ```validateConfig()```


## Use of standalone implementation


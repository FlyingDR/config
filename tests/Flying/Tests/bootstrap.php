<?php

namespace Flying\Tests;

$composer = false;
for ($i = 0; $i < 10; $i++) {
    $path = __DIR__ . '/' . str_repeat('../', $i) . 'vendor/autoload.php';
    if (is_file($path)) {
        require_once $path;
        $composer = true;
        break;
    }
}
if (!$composer) {
    spl_autoload_register(function ($class) {
        $map = array(
            'Flying\\Tests\\'  => '/../../',
            'Flying\\Config\\' => '/../../../lib/',
        );
        foreach ($map as $prefix => $path) {
            if (0 === strpos($class, $prefix)) {
                $path = __DIR__ . $path . strtr($class, '\\', '/') . '.php';
                if (is_file($path) && is_readable($path)) {
                    require_once $path;
                    return true;
                }
            }
        }
        return false;
    });
}

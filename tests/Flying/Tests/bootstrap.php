<?php

namespace Flying\Tests;

$composer = false;
for ($i = 0; $i < 10; $i++) {
    $path = __DIR__ . '/' . str_repeat('../', $i) . 'vendor/autoload.php';
    if (is_file($path)) {
        /** @noinspection PhpIncludeInspection */
        require_once $path;
        $composer = true;
        break;
    }
}
if (!$composer) {
    spl_autoload_register(function ($class) {
        $map = [
            'Flying\\Tests\\'  => '/../../',
            'Flying\\Config\\' => '/../../../lib/',
        ];
        foreach ($map as $prefix => $path) {
            if (0 === strpos($class, $prefix)) {
                $path = __DIR__ . $path . str_replace('\\', '/', $class) . '.php';
                if (is_file($path) && is_readable($path)) {
                    /** @noinspection PhpIncludeInspection */
                    require_once $path;
                    return true;
                }
            }
        }
        return false;
    });
}

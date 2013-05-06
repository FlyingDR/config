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
    // Autoloader function is taken from Doctrine tests bootstrap
    spl_autoload_register(function ($class) {
        if (0 === strpos($class, 'Flying\\Tests\\')) {
            $path = __DIR__ . '/../../' . strtr($class, '\\', '/') . '.php';
            if (is_file($path) && is_readable($path)) {
                require_once $path;
                return true;
            }
        }
        return false;
    });
}

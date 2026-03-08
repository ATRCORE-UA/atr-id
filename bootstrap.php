<?php

declare(strict_types=1);

/**
 * ATR-ID SDK autoloader for projects that do not use Composer.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'AtrId\\Auth\\';
    $baseDir = __DIR__ . '/src/AtrId/Auth/';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

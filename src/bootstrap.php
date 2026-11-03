<?php

/**
 * Class autoloader (LegalQueryWeb\Foo\Bar -> src/Foo/Bar.php) and helper functions.
 * No Composer: the site has no third-party dependencies.
 */

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'LegalQueryWeb\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require __DIR__ . '/helpers.php';

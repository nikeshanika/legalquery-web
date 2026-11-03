<?php

/**
 * Front controller: every page request comes here (see .htaccess and router.php).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$config = require dirname(__DIR__) . '/config.php';
(new LegalQueryWeb\App($config))->run($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

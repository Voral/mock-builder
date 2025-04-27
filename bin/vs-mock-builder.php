#!/usr/bin/env php
<?php

declare(strict_types=1);

use Vasoft\MockBuilder\Application;
set_time_limit(0);
@ini_set('implicit_flush', 1);
ob_implicit_flush();

$autoloadPaths = [
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/../../../autoload.php',
    getcwd() . '/vendor/autoload.php',
];

foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require $path;
        break;
    }
}
(new Application())->run();

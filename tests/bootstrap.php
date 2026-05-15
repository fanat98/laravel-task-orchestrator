<?php

declare(strict_types=1);

$packageAutoload = __DIR__.'/../vendor/autoload.php';
$hostAutoload = __DIR__.'/../../../vendor/autoload.php';

if (is_file($packageAutoload)) {
    $loader = require $packageAutoload;
} else {
    $loader = require $hostAutoload;
}

$loader->addPsr4('Malsa\\TaskOrchestrator\\Tests\\', __DIR__.'/');

return $loader;

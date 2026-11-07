<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Auto-patch bug PCRE regex "Null byte in regex" pada Str.php di hosting Linux
$strFile = __DIR__.'/../vendor/laravel/framework/src/Illuminate/Support/Str.php';
if (file_exists($strFile)) {
    $strCode = file_get_contents($strFile);
    if (strpos($strCode, '" \n\r\t\v\0"') !== false) {
        @file_put_contents($strFile, str_replace('" \n\r\t\v\0"', '" \n\r\t\v"', $strCode));
    }
}

// Auto-rename folder Auth ke auth (Linux case sensitivity)
$authUpper = __DIR__.'/../resources/views/Auth';
$authLower = __DIR__.'/../resources/views/auth';
if (is_dir($authUpper) && !is_dir($authLower)) {
    @rename($authUpper, $authLower);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$publicDir = __DIR__;
$parentDir = dirname($publicDir);

$candidates = [
    $parentDir.'/laravel',
    $parentDir.'/motoworld',
    $parentDir,
];

$laravelRoot = null;

foreach ($candidates as $candidate) {
    $resolved = realpath($candidate) ?: $candidate;

    if (is_file($resolved.'/vendor/autoload.php') && is_file($resolved.'/bootstrap/app.php')) {
        $laravelRoot = $resolved;
        break;
    }
}

if ($laravelRoot === null) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit(
        "No se encontró Laravel (vendor/autoload.php).\n".
        "public_html: {$publicDir}\n".
        "Buscado en: {$parentDir} y {$parentDir}/laravel\n"
    );
}

if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());

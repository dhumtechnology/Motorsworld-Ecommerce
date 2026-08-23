<?php

declare(strict_types=1);

/**
 * Asistente de despliegue para cPanel sin SSH.
 *
 * 1. En el .env de producción (raíz de Laravel): CPANEL_DEPLOY_TOKEN=tu_clave_secreta
 * 2. Visita: https://TU-DOMINIO-REAL.com/cpanel-deploy.php?token=tu_clave_secreta
 * 3. Borra este archivo del servidor en cuanto termine.
 *
 * Si el .env no se lee, descomenta CPANEL_DEPLOY_TOKEN_OVERRIDE abajo (solo temporal).
 */

// define('CPANEL_DEPLOY_TOKEN_OVERRIDE', 'pon_aqui_la_misma_clave');
// Estructura cPanel (Laravel fuera de public_html): descomenta y ajusta si hace falta.
// define('LARAVEL_ROOT_OVERRIDE', '/home/motoworld/laravel');

$isLaravelRoot = static function (string $path): bool {
    return is_file($path.'/.env')
        && is_file($path.'/artisan')
        && is_file($path.'/bootstrap/app.php');
};

$findLaravelRoot = static function (string $start) use ($isLaravelRoot): ?string {
    if (defined('LARAVEL_ROOT_OVERRIDE')) {
        $override = rtrim((string) LARAVEL_ROOT_OVERRIDE, '/');

        return $isLaravelRoot($override) ? $override : null;
    }

    $candidates = [];

    $startReal = realpath($start) ?: $start;
    $candidates[] = $startReal;

    // cPanel típico: /home/usuario/public_html + /home/usuario/laravel
    $homeDir = dirname($startReal);
    $candidates[] = $homeDir.'/laravel';
    $candidates[] = $homeDir.'/motoworld';
    $candidates[] = $homeDir.'/app';

    foreach ($candidates as $candidate) {
        $resolved = realpath($candidate) ?: $candidate;

        if ($isLaravelRoot($resolved)) {
            return $resolved;
        }
    }

    $dir = $startReal;

    while ($dir !== false && $dir !== dirname($dir)) {
        if ($isLaravelRoot($dir)) {
            return $dir;
        }

        $dir = dirname($dir);
    }

    return null;
};

$readEnvToken = static function (string $envFile): ?string {
    if (! is_readable($envFile)) {
        return null;
    }

    $contents = file_get_contents($envFile);

    if ($contents === false) {
        return null;
    }

    // Quitar BOM UTF-8 si existe.
    $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

    foreach (preg_split('/\R/', $contents) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (preg_match('/^([\'"])(.*)\1$/', $value, $matches) === 1) {
            $value = $matches[2];
        }

        if ($name === 'CPANEL_DEPLOY_TOKEN') {
            return $value !== '' ? $value : null;
        }
    }

    return null;
};

header('Content-Type: text/plain; charset=utf-8');

$root = $findLaravelRoot(__DIR__);

if ($root === null) {
    http_response_code(500);
    exit(
        "No se encontró la raíz de Laravel (falta .env + artisan).\n".
        "Ubicación del script: ".__DIR__."\n".
        "Buscado también en: ".dirname(__DIR__)."/laravel\n".
        "Si tu carpeta tiene otro nombre, define LARAVEL_ROOT_OVERRIDE en cpanel-deploy.php.\n"
    );
}

$expectedToken = defined('CPANEL_DEPLOY_TOKEN_OVERRIDE')
    ? (string) CPANEL_DEPLOY_TOKEN_OVERRIDE
    : $readEnvToken($root.'/.env');

$providedToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($expectedToken === null || $expectedToken === '') {
    http_response_code(403);
    exit(
        "CPANEL_DEPLOY_TOKEN no está definido.\n\n".
        "En el .env del servidor ({$root}/.env) agrega una línea como:\n".
        "CPANEL_DEPLOY_TOKEN=TuClaveSecreta123\n\n".
        "Luego abre:\n".
        "https://tu-dominio.com/cpanel-deploy.php?token=TuClaveSecreta123\n\n".
        "Usa TU dominio real y la MISMA clave en .env y en la URL.\n"
    );
}

if ($providedToken === '') {
    http_response_code(403);
    exit(
        "Falta el parámetro token en la URL.\n\n".
        "Ejemplo:\n".
        "https://tu-dominio.com/cpanel-deploy.php?token=TU_CLAVE_DEL_ENV\n"
    );
}

if (! hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    exit(
        "Token incorrecto.\n\n".
        "La clave de la URL no coincide con CPANEL_DEPLOY_TOKEN del .env.\n".
        "Revisa que no haya espacios, comillas extra ni mayúsculas/minúsculas distintas.\n".
        "Archivo .env leído desde: {$root}/.env\n"
    );
}

if (! is_file($root.'/vendor/autoload.php')) {
    http_response_code(500);
    exit("Falta vendor/ en {$root}. Sube la carpeta vendor o ejecuta composer install.\n");
}

define('LARAVEL_START', microtime(true));

require $root.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $root.'/bootstrap/app.php';

/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Motoworld — despliegue cPanel\n";
echo "Raíz Laravel: {$root}\n";
echo str_repeat('=', 40)."\n\n";

$commands = [
    'migrate --force',
    'storage:link',
    'config:cache',
    'route:cache',
    'view:cache',
];

foreach ($commands as $command) {
    echo ">>> php artisan {$command}\n";

    try {
        $status = $kernel->call($command);
        $output = trim((string) $kernel->output());

        if ($output !== '') {
            echo $output."\n";
        }

        echo 'Estado: '.($status === 0 ? 'OK' : "Error ({$status})")."\n\n";
    } catch (Throwable $exception) {
        echo 'Error: '.$exception->getMessage()."\n\n";
    }
}

echo str_repeat('=', 40)."\n";
echo "Listo.\n";
echo "IMPORTANTE: elimina public/cpanel-deploy.php del servidor ahora.\n";

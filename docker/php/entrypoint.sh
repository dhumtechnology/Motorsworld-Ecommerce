#!/bin/sh
set -e

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

export TMPDIR=/var/www/html/storage/framework/cache

if [ ! -f "vendor/autoload.php" ] || [ "composer.lock" -nt "vendor/autoload.php" ]; then
    echo "Instalando dependencias de Composer..."
    composer install --prefer-dist --no-interaction
fi

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "Generando APP_KEY..."
    php artisan key:generate --force --ansi
fi

# Conexión interna Docker: siempre mysql:3306 (DB_PORT_EXTERNAL solo mapea el host).
export DB_HOST=mysql
export DB_PORT=3306

echo "Esperando conexión a la base de datos (mysql:3306)..."
max_retries=30
retry=0
until php artisan db:show >/dev/null 2>&1; do
    retry=$((retry + 1))
    if [ "$retry" -ge "$max_retries" ]; then
        echo "Error: no se pudo conectar a la base de datos tras $max_retries intentos."
        exit 1
    fi
    echo "Base de datos no disponible, reintentando ($retry/$max_retries)..."
    sleep 2
done

echo "Aplicando migraciones pendientes..."
if ! php artisan migrate --force --no-interaction; then
    echo "Advertencia: no se pudieron aplicar todas las migraciones. Revise con: php artisan migrate:status"
fi

if [ "${SEED_ON_START:-true}" = "true" ]; then
    echo "Ejecutando seeders (upsert)..."
    if ! php artisan db:seed --force --no-interaction; then
        echo "Advertencia: no se pudieron ejecutar los seeders. Revise los logs de la aplicación."
    fi
else
    echo "Seeders omitidos (SEED_ON_START=false)."
fi

exec docker-php-entrypoint "$@"

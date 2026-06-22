#!/bin/sh
set -e

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

export TMPDIR=/var/www/html/storage/framework/cache

if [ ! -d "vendor" ]; then
    echo "Instalando dependencias de Composer..."
    composer install --prefer-dist --no-interaction
fi

if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate --ansi
fi

echo "Esperando conexión a la base de datos..."
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

exec docker-php-entrypoint "$@"

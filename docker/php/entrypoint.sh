#!/bin/sh
set -e

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
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

exec docker-php-entrypoint "$@"

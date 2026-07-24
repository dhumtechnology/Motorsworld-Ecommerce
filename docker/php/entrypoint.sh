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

# Seeders en segundo plano para que PHP-FPM (y nginx) no esperen 1–2 min.
# SEED_ON_START=auto (default): solo si la BD no tiene usuarios.
# SEED_ON_START=true: siempre. SEED_ON_START=false: nunca.
run_seeders_if_needed() {
    case "${SEED_ON_START:-auto}" in
        false|0|no|NO|False)
            echo "Seeders omitidos (SEED_ON_START=false)."
            return 0
            ;;
        true|1|yes|YES|True)
            echo "Ejecutando seeders (SEED_ON_START=true)..."
            php artisan db:seed --force --no-interaction || echo "Advertencia: fallaron los seeders."
            return 0
            ;;
        *)
            has_users="$(php -r '
                try {
                    $pdo = new PDO(
                        sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST") ?: "mysql", getenv("DB_PORT") ?: "3306", getenv("DB_DATABASE") ?: "motosworld"),
                        getenv("DB_USERNAME") ?: "motosworld",
                        getenv("DB_PASSWORD") ?: "secret"
                    );
                    echo (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                } catch (Throwable $e) {
                    echo "0";
                }
            ' 2>/dev/null || echo "0")"

            if [ "${has_users:-0}" -gt 0 ] 2>/dev/null; then
                echo "BD ya tiene datos → omitiendo seeders (SEED_ON_START=auto)."
            else
                echo "BD vacía → ejecutando seeders (SEED_ON_START=auto)..."
                php artisan db:seed --force --no-interaction || echo "Advertencia: fallaron los seeders."
            fi
            ;;
    esac
}

echo "Levantando PHP-FPM (seeders en segundo plano si aplican)..."
run_seeders_if_needed &

exec docker-php-entrypoint "$@"

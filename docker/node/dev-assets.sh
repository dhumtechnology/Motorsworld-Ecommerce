#!/bin/sh
set -e

cd /var/www/html

# SKIP_ASSET_BUILD=true → no arranca Vite (útil en CI o si solo usas public/build).
if [ "${SKIP_ASSET_BUILD:-false}" = "true" ]; then
    echo "SKIP_ASSET_BUILD=true → omitiendo Vite watch."
    if [ ! -f public/build/manifest.json ]; then
        echo "ADVERTENCIA: no existe public/build/manifest.json; la UI saldrá sin estilos."
    fi
    exit 0
fi

echo "Instalando dependencias npm..."
if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund
else
    npm install --no-audit --no-fund
fi

# Build de respaldo: Laravel usa public/build si Vite aún no escribió public/hot.
if [ ! -f public/build/manifest.json ] || [ "${FORCE_ASSET_BUILD:-false}" = "true" ]; then
    echo "Generando build inicial de assets..."
    npm run build
fi

echo "Vite watch activo → guardar Blade/CSS/JS regenera Tailwind (polling para Docker Desktop)."
echo "No hace falta reiniciar contenedores al cambiar clases."
exec npm run dev -- --host 0.0.0.0 --port 5173

#!/bin/sh
set -e

cd /var/www/html

# Por defecto SIEMPRE se compilan los assets (public/build está en .gitignore).
# Para omitir: SKIP_ASSET_BUILD=true docker compose up
if [ "${SKIP_ASSET_BUILD:-false}" = "true" ]; then
    echo "SKIP_ASSET_BUILD=true → omitiendo compilación de assets."
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

echo "Compilando assets con Vite..."
npm run build

if [ ! -f public/build/manifest.json ]; then
    echo "ERROR: Vite no generó public/build/manifest.json"
    exit 1
fi

echo "Assets compilados en public/build/"

#!/bin/sh
set -e

cd /var/www/html

if [ -f public/build/manifest.json ] && [ "${FORCE_ASSET_BUILD:-false}" != "true" ]; then
    echo "Assets ya compilados (public/build/manifest.json). Omitiendo build."
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

echo "Assets compilados en public/build/"

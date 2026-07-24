#!/bin/sh
set -e

cd /var/www/html

# SKIP_ASSET_BUILD=true → no compila (útil si ya tienes public/build).
# FORCE_ASSET_BUILD=true → compila siempre.
# Por defecto: solo compila si no hay manifest o si cambiaron fuentes npm/vite/css/js/views.
if [ "${SKIP_ASSET_BUILD:-false}" = "true" ]; then
    echo "SKIP_ASSET_BUILD=true → omitiendo compilación de assets."
    if [ ! -f public/build/manifest.json ]; then
        echo "ADVERTENCIA: no existe public/build/manifest.json; la UI saldrá sin estilos."
    fi
    exit 0
fi

needs_build=true
if [ "${FORCE_ASSET_BUILD:-false}" != "true" ] && [ -f public/build/manifest.json ]; then
    newer="$(find \
        package.json \
        package-lock.json \
        vite.config.js \
        resources/css \
        resources/js \
        resources/views \
        -type f \
        -newer public/build/manifest.json \
        2>/dev/null | head -n 1 || true)"

    if [ -z "$newer" ]; then
        echo "Assets ya compilados y al día → omitiendo Vite."
        needs_build=false
    else
        echo "Cambios detectados en assets ($newer) → recompilando..."
    fi
fi

if [ "$needs_build" = "false" ]; then
    exit 0
fi

echo "Instalando dependencias npm..."
if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund
else
    npm install --no-audit --no-fund
fi

echo "Compilando assets con Vite (en Docker Desktop/Windows puede tardar 1–3 min)..."
npm run build

if [ ! -f public/build/manifest.json ]; then
    echo "ERROR: Vite no generó public/build/manifest.json"
    exit 1
fi

echo "Assets compilados en public/build/"
echo "Listo. Recarga el navegador si la UI salía sin estilos."

#!/bin/sh
set -e

cd /var/www/html

# SKIP_ASSET_BUILD=true → no corre Vite (útil si usas `npm run dev` en el host).
if [ "${SKIP_ASSET_BUILD:-false}" = "true" ]; then
    echo "SKIP_ASSET_BUILD=true → omitiendo Vite."
    if [ ! -f public/build/manifest.json ] && [ ! -f public/hot ]; then
        echo "ADVERTENCIA: no hay public/build ni public/hot; la UI saldrá sin estilos."
    fi
    exit 0
fi

echo "Instalando dependencias npm..."
if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund
else
    npm install --no-audit --no-fund
fi

mode="${ASSET_MODE:-watch}"

# once → una sola build (CI).
if [ "$mode" = "once" ]; then
    echo "ASSET_MODE=once → compilando assets una vez..."
    rm -f public/hot
    npm run build
    if [ ! -f public/build/manifest.json ]; then
        echo "ERROR: Vite no generó public/build/manifest.json"
        exit 1
    fi
    echo "Assets compilados en public/build/"
    exit 0
fi

# dev → HMR en :5173 (más lento en Docker Desktop; útil solo si lo necesitas).
if [ "$mode" = "dev" ]; then
    echo "ASSET_MODE=dev → Vite HMR en :5173 (puede ser lento en Docker)."
    exec npm run dev -- --host 0.0.0.0 --port 5173
fi

# watch (default) → public/build + recompilar al guardar Blade/CSS/JS (páginas rápidas).
echo "ASSET_MODE=watch → public/build + watcher de Blade/CSS/JS."
exec npm run build:watch

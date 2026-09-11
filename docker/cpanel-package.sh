#!/bin/sh
set -e

ROOT="${ROOT:-/var/www/html}"
STAGING="/tmp/motoworld-cpanel"
STAMP="$(date +%Y%m%d-%H%M)"
ZIP_NAME="motoworld-cpanel-${STAMP}.zip"
OUT_DIR="${ROOT}/dist"

cd "$ROOT"

if [ ! -f public/build/manifest.json ]; then
    echo "ERROR: falta public/build/manifest.json. Compila assets antes (ASSET_MODE=once)."
    exit 1
fi

echo ">>> Composer producción (--no-dev --optimize-autoloader)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

rm -rf "$STAGING"
mkdir -p "$STAGING/laravel" "$STAGING/public_html" "$OUT_DIR"

echo ">>> Copiando raíz Laravel"
for item in app bootstrap config database lang resources routes artisan composer.json composer.lock; do
    if [ -e "$ROOT/$item" ]; then
        cp -a "$ROOT/$item" "$STAGING/laravel/"
    fi
done

cp -a "$ROOT/vendor" "$STAGING/laravel/"
cp -a "$ROOT/.env.example" "$STAGING/laravel/" 2>/dev/null || true

echo ">>> Limpiando caché de rutas/config del paquete (evita 500 en cPanel)"
find "$STAGING/laravel/bootstrap/cache" -type f ! -name '.gitignore' ! -name 'packages.php' ! -name 'services.php' -delete 2>/dev/null || true

echo ">>> Storage vacío (no se suben fotos locales)"
mkdir -p \
    "$STAGING/laravel/storage/app/public" \
    "$STAGING/laravel/storage/app/private" \
    "$STAGING/laravel/storage/framework/cache/data" \
    "$STAGING/laravel/storage/framework/sessions" \
    "$STAGING/laravel/storage/framework/testing" \
    "$STAGING/laravel/storage/framework/views" \
    "$STAGING/laravel/storage/logs" \
    "$STAGING/laravel/bootstrap/cache"

if [ -d "$ROOT/storage/app/public" ]; then
    find "$ROOT/storage/app/public" -maxdepth 1 -name '.gitignore' -exec cp {} "$STAGING/laravel/storage/app/public/" \;
fi

echo ">>> public_html (incluye CSS/JS de Vite)"
cp -a "$ROOT/public/." "$STAGING/public_html/"
rm -f "$STAGING/public_html/hot" "$STAGING/public_html/storage"
cp "$ROOT/docker/cpanel-index.php" "$STAGING/public_html/index.php"

cat > "$STAGING/LEEME-CPANEL.txt" <<'EOF'
Motoworld — paquete cPanel
==========================

Estructura esperada en el servidor:

  /home/USUARIO/laravel/       <- contenido de la carpeta laravel/
  /home/USUARIO/public_html/   <- contenido de la carpeta public_html/

Pasos
-----
1. En File Manager, NO borres el .env de producción ni storage/app/public
   (fotos de productos).
2. Sube y extrae:
   - laravel/*        → /home/USUARIO/laravel/
   - public_html/*    → /home/USUARIO/public_html/
3. Permisos 775 (o 755) en laravel/storage y laravel/bootstrap/cache.
4. El .env de producción debe tener:
     APP_ENV=production
     APP_DEBUG=false
     CULQI_FAKE=false
     CULQI_PUBLIC_KEY=pk_live_...   (o pk_test_ si aún es staging)
     CULQI_SECRET_KEY=sk_live_...
     CPANEL_DEPLOY_TOKEN=una_clave_secreta
5. Abre una sola vez:
     https://TU-DOMINIO/cpanel-deploy.php?token=TU_CLAVE
6. Borra public_html/cpanel-deploy.php del servidor.

No subas el .env de tu PC.
EOF

echo ">>> Generando ZIP"
rm -f "$OUT_DIR"/motoworld-cpanel-*.zip
cd "$STAGING"
zip -qr "$OUT_DIR/$ZIP_NAME" laravel public_html LEEME-CPANEL.txt

echo ">>> Restaurando Composer de desarrollo"
cd "$ROOT"
composer install --no-interaction --prefer-dist

echo
echo "Listo: dist/$ZIP_NAME"
ls -lh "$OUT_DIR/$ZIP_NAME"

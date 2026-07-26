#!/bin/sh
set -e

# Compilación única (CI / forzar rebuild). En desarrollo diario usa docker/node/dev-assets.sh.
cd /var/www/html

export ASSET_MODE=once
export SKIP_ASSET_BUILD="${SKIP_ASSET_BUILD:-false}"

exec sh docker/node/dev-assets.sh

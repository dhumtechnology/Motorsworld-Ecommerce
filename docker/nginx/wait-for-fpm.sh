#!/bin/sh
set -e

echo "Esperando PHP-FPM en app:9000..."
i=0
while ! nc -z app 9000; do
    i=$((i + 1))
    if [ "$i" -ge 90 ]; then
        echo "ERROR: PHP-FPM no respondió a tiempo en app:9000"
        exit 1
    fi
    sleep 2
done

echo "PHP-FPM listo → iniciando nginx."
exec nginx -g 'daemon off;'

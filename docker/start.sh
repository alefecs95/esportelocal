#!/bin/sh
set -e

nginx -t
php-fpm -D
echo "nginx: escutando na porta 80"
wget -q -O- http://127.0.0.1/health && echo "healthcheck interno: ok" || echo "healthcheck interno: FALHOU" >&2
exec nginx -g 'daemon off;'

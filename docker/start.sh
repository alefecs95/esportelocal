#!/bin/sh
set -e

nginx -t
php-fpm -D
nginx -g 'daemon off;' &
NGINX_PID=$!

# Esperar nginx ficar pronto
sleep 1
if wget -q -O- http://127.0.0.1/health >/dev/null 2>&1; then
    echo "healthcheck interno: ok"
else
    echo "healthcheck interno: FALHOU (nginx pode precisar de mais tempo)" >&2
fi

echo "nginx: escutando na porta 80 (pid $NGINX_PID)"
wait $NGINX_PID

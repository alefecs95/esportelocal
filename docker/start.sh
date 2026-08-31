#!/bin/sh
set -e

nginx -t
php-fpm -D
echo "nginx: escutando na porta 80"
exec nginx -g 'daemon off;'

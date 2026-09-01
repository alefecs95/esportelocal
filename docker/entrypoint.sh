#!/bin/sh
set -e

cd /var/www/html/backend

auto_configure_urls() {
    base=""

    if [ -n "${COOLIFY_URL:-}" ]; then
        base="${COOLIFY_URL%/}"
    elif [ -n "${SERVICE_URL_APP_80:-}" ]; then
        base="${SERVICE_URL_APP_80%/}"
    elif [ -n "${COOLIFY_FQDN:-}" ]; then
        base="https://${COOLIFY_FQDN%/}"
    fi

    if [ -n "$base" ]; then
        case "$base" in
            //*) base="https:${base}" ;;
            http://*) base="https://${base#http://}" ;;
            https://*) ;;
            *) base="https://${base}" ;;
        esac
    fi

    if [ -n "$base" ]; then
        if [ -z "${FRONTEND_URL:-}" ]; then
            export FRONTEND_URL="$base"
        fi
        if [ -z "${APP_URL:-}" ]; then
            export APP_URL="${base}/backend/public"
        fi
    fi

    echo "APP_URL=${APP_URL:-não definido}"
    echo "FRONTEND_URL=${FRONTEND_URL:-não definido}"
}

wait_for_mysql() {
    if [ -z "${DB_HOST:-}" ]; then
        return 0
    fi

    echo "Aguardando MySQL (${DB_HOST}:${DB_PORT:-3306})..."
    attempts=0
    max_attempts=60

    while [ "$attempts" -lt "$max_attempts" ]; do
        if php -r "
            try {
                new PDO(
                    'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'),
                    getenv('DB_USERNAME') ?: 'root',
                    getenv('DB_PASSWORD') ?: '',
                    [PDO::ATTR_TIMEOUT => 2]
                );
                exit(0);
            } catch (Throwable \$e) {
                exit(1);
            }
        "; then
            echo "MySQL disponível."
            return 0
        fi

        attempts=$((attempts + 1))
        sleep 2
    done

    echo "Timeout aguardando MySQL." >&2
    exit 1
}

database_has_tables() {
    php -r "
        try {
            \$pdo = new PDO(
                'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD'),
                [PDO::ATTR_TIMEOUT => 5]
            );
            \$count = (int) \$pdo->query('SHOW TABLES')->rowCount();
            exit(\$count > 0 ? 0 : 1);
        } catch (Throwable \$e) {
            exit(1);
        }
    "
}

import_database_if_needed() {
    if [ "${IMPORT_DB_ON_START:-false}" != "true" ]; then
        return 0
    fi

    import_file="${IMPORT_DB_FILE:-/docker/database/import/backup.sql}"
    if [ ! -f "$import_file" ]; then
        echo "IMPORT_DB_ON_START=true, mas backup não encontrado em: $import_file"
        return 0
    fi

    if database_has_tables; then
        echo "Banco já contém tabelas — importação automática ignorada."
        return 0
    fi

    echo "Importando backup SQL: $import_file"
    mysql \
        -h"${DB_HOST}" \
        -P"${DB_PORT:-3306}" \
        -u"${DB_USERNAME}" \
        -p"${DB_PASSWORD}" \
        "${DB_DATABASE}" < "$import_file"
    echo "Backup importado com sucesso."
}

prepare_storage() {
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p storage/app/public/tenants
    mkdir -p bootstrap/cache

    chown -R www:www storage bootstrap/cache
    chmod -R ug+rwx storage bootstrap/cache

    rm -f public/storage public/tenants 2>/dev/null || true
    php artisan storage:link --force 2>/dev/null || true
}

run_migrations_if_needed() {
    if [ "${RUN_MIGRATIONS:-true}" != "true" ]; then
        return 0
    fi

    if [ -z "${APP_KEY:-}" ]; then
        echo "APP_KEY não definida — migrations ignoradas." >&2
        return 0
    fi

    echo "Executando migrations..."
    php artisan migrate --force || echo "Aviso: migrations falharam (container continua)." >&2
}

cache_configuration_if_needed() {
    if [ "${RUN_CONFIG_CACHE:-true}" != "true" ]; then
        return 0
    fi

    if [ -z "${APP_KEY:-}" ]; then
        return 0
    fi

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
}

run_deferred_setup() {
    wait_for_mysql || {
        echo "Setup em background: MySQL indisponível." >&2
        return 1
    }
    import_database_if_needed
    run_migrations_if_needed
    cache_configuration_if_needed
    echo "Setup em background concluído."
}

auto_configure_urls
prepare_storage

# MySQL/migrations em background — nginx sobe imediatamente (evita 504 do proxy)
run_deferred_setup &

exec "$@"

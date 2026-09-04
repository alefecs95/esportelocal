#!/bin/sh
set -e

cd /var/www/html/backend

# Coolify concatena vários domínios com vírgula (ex.: https://a.com,https://www.a.com).
# Laravel APP_URL/FRONTEND_URL precisam ser uma URI única — senão: "Invalid URI: Host is malformed."
first_url() {
    val="${1:-}"
    val="${val%%,*}"
    val="${val%% }"
    val="${val%/}"
    echo "$val"
}

normalize_https_url() {
    url="$(first_url "${1:-}")"
    [ -z "$url" ] && return 0

    case "$url" in
        //*) url="https:${url}" ;;
        http://*) url="https://${url#http://}" ;;
        https://*) ;;
        *) url="https://${url}" ;;
    esac

    echo "${url%/}"
}

ensure_public_path() {
    url="${1:-}"
    public_path="${APP_PUBLIC_PATH:-/backend/public}"
    public_path="/${public_path#/}"
    public_path="${public_path%/}"

    [ -z "$url" ] && echo "$url" && return 0

    case "$url" in
        *"${public_path}") echo "$url" ;;
        *) echo "${url}${public_path}" ;;
    esac
}

auto_configure_urls() {
    base=""

    if [ -n "${COOLIFY_URL:-}" ]; then
        base="$(normalize_https_url "$COOLIFY_URL")"
    elif [ -n "${SERVICE_URL_APP_80:-}" ]; then
        base="$(normalize_https_url "$SERVICE_URL_APP_80")"
    elif [ -n "${COOLIFY_FQDN:-}" ]; then
        base="$(normalize_https_url "$COOLIFY_FQDN")"
    fi

    if [ -n "${FRONTEND_URL:-}" ]; then
        export FRONTEND_URL="$(normalize_https_url "$FRONTEND_URL")"
    elif [ -n "$base" ]; then
        export FRONTEND_URL="$base"
    fi

    if [ -n "${APP_URL:-}" ]; then
        export APP_URL="$(ensure_public_path "$(normalize_https_url "$APP_URL")")"
    elif [ -n "$base" ]; then
        export APP_URL="$(ensure_public_path "$base")"
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

    import_file="${IMPORT_DB_FILE:-/opt/seed/backup.sql}"
    if [ ! -f "$import_file" ] && [ -f /opt/seed/backup.sql ]; then
        import_file="/opt/seed/backup.sql"
    fi
    if [ ! -f "$import_file" ] && [ -f /docker/database/import/backup.sql ]; then
        import_file="/docker/database/import/backup.sql"
    fi
    if [ ! -f "$import_file" ]; then
        echo "IMPORT_DB_ON_START=true, mas backup não encontrado em: $import_file"
        return 0
    fi

    if [ "${FORCE_IMPORT_DB:-false}" != "true" ] && database_has_tables; then
        echo "Banco já contém tabelas — importação automática ignorada."
        echo "Para substituir os dados: FORCE_IMPORT_DB=true (o dump precisa ter DROP TABLE)."
        return 0
    fi

    if [ "${FORCE_IMPORT_DB:-false}" = "true" ]; then
        echo "FORCE_IMPORT_DB=true — importando por cima do banco atual."
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

import_storage_if_needed() {
    dest="storage/app/public"
    mkdir -p "$dest"

    seed_dir="${IMPORT_STORAGE_DIR:-/opt/seed/public}"
    if [ -d "$seed_dir" ]; then
        echo "Copiando mídias de $seed_dir -> $dest"
        find "$seed_dir" -type f | while IFS= read -r src; do
            rel="${src#"$seed_dir"/}"
            tgt="$dest/$rel"
            if [ ! -f "$tgt" ]; then
                mkdir -p "$(dirname "$tgt")"
                cp "$src" "$tgt"
            fi
        done
        echo "Mídias copiadas."
    fi

    zip_file="${IMPORT_STORAGE_FILE:-}"
    if [ -z "$zip_file" ] || [ ! -f "$zip_file" ]; then
        return 0
    fi

    echo "Extraindo mídias: $zip_file -> $dest"
    if [ "${FORCE_IMPORT_STORAGE:-false}" = "true" ]; then
        unzip -o "$zip_file" -d "$dest"
    else
        unzip -n "$zip_file" -d "$dest"
    fi
    echo "Mídias extraídas."
}

prepare_storage() {
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p storage/app/public/tenants
    mkdir -p bootstrap/cache

    import_storage_if_needed

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

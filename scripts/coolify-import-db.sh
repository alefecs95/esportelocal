#!/bin/sh
# Importa backup SQL no MySQL do stack Coolify/Docker Compose.
#
# Uso (no servidor ou via Coolify > Terminal):
#   sh scripts/coolify-import-db.sh /caminho/para/backup.sql
#
# Exemplo com volume db_import:
#   sh scripts/coolify-import-db.sh /docker/database/import/backup.sql

set -e

BACKUP_FILE="${1:-/docker/database/import/backup.sql}"
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-semel}"
DB_USERNAME="${DB_USERNAME:-semel}"
DB_PASSWORD="${DB_PASSWORD:-${SERVICE_PASSWORD_MYSQL}}"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Arquivo não encontrado: $BACKUP_FILE" >&2
    exit 1
fi

if [ -z "$DB_PASSWORD" ]; then
    echo "Defina DB_PASSWORD ou SERVICE_PASSWORD_MYSQL." >&2
    exit 1
fi

echo "Importando $BACKUP_FILE em ${DB_HOST}/${DB_DATABASE}..."

mysql \
    -h"$DB_HOST" \
    -P"$DB_PORT" \
    -u"$DB_USERNAME" \
    -p"$DB_PASSWORD" \
    "$DB_DATABASE" < "$BACKUP_FILE"

echo "Importação concluída."

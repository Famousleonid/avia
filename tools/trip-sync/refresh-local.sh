#!/usr/bin/env bash
# ------------------------------------------------------------------
# refresh-local.sh — обновление локальной базы (флешки) с прода.
# ОБЯЗАТЕЛЕН перед каждой локальной сессией, если после прошлой
# синхронизации вы работали на проде (aviatechnik.ca).
#
#   ./refresh-local.sh prod-dump.sql[.gz]
#
# Дамп берётся с прода (SSH: mysqldump; либо экспорт в phpMyAdmin).
# Годится и полный дамп базы, и частичный — минимум это таблицы из
# tables-owned.txt + tables-shared.txt + media (workorders и прочее
# продовое для локальной работы не обязательны и могут остаться
# устаревшими).
#
# После заливки автоматически запускает prepare-trip.sh: выбирается
# новая полоса id и снимается новый baseline.
# ------------------------------------------------------------------
set -euo pipefail
cd "$(dirname "$0")"

DUMPFILE="${1:?Использование: ./refresh-local.sh prod-dump.sql[.gz]}"
DB_NAME="${DB_NAME:-avia}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

MYSQL=(mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME")

echo "== Заливка дампа прода: $DUMPFILE =="
case "$DUMPFILE" in
    *.gz) gzip -dc "$DUMPFILE" | "${MYSQL[@]}" ;;
    *)    "${MYSQL[@]}" < "$DUMPFILE" ;;
esac
echo "  залито"

rm -f trip-offset.txt
./prepare-trip.sh

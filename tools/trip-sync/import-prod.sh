#!/usr/bin/env bash
# ------------------------------------------------------------------
# import-prod.sh — применяет архив trip-manual-*.tar.gz к базе.
# Запускается там, где доступна целевая база (прод по SSH, либо дома
# для репетиции/обновления домашней копии).
#
#   ./import-prod.sh trip-manual-2026-08-25.tar.gz
#
# Порядок работы:
#  1. ПРЕДОХРАНИТЕЛЬ: сверяет MAX(id) "своих" таблиц с baseline.tsv.
#     Если на целевой базе в этих таблицах появились новые строки
#     (кто-то вводил manual-данные параллельно) — СТОП, разбираться
#     руками. Обойти проверку: FORCE=1 ./import-prod.sh <архив>
#  2. Снимает страховочный дамп затрагиваемых таблиц.
#  3. Применяет owned.sql, shared-replace.sql, media-new.sql.
#  4. Напоминает распаковать files.tar.gz в storage/app/public.
#
# Если на хостинге нет SSH — см. README, раздел "Через phpMyAdmin".
# ------------------------------------------------------------------
set -euo pipefail

ARCHIVE="${1:?Использование: ./import-prod.sh trip-manual-ГГГГ-ММ-ДД.tar.gz}"
DB_NAME="${DB_NAME:-avia}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

MYSQL=(mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME")
DUMP=(mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} --single-transaction "$DB_NAME")

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
tar xzf "$ARCHIVE" -C "$TMP"

echo "== 1/3 Проверка предохранителя =="
FAILED=0
while IFS=$'\t' read -r t base; do
    cur=$("${MYSQL[@]}" -N -e "SELECT COALESCE(MAX(id),0) FROM \`$t\`" 2>/dev/null || echo "?")
    if [ "$cur" = "?" ]; then
        echo "  ВНИМАНИЕ: таблицы $t нет в целевой базе (новая таблица? примените миграции)"
        FAILED=1
    elif [ "$cur" -gt "$base" ]; then
        echo "  КОНФЛИКТ: $t — на целевой базе MAX(id)=$cur, в baseline=$base (кто-то добавлял строки!)"
        FAILED=1
    fi
done < "$TMP/baseline.tsv"
if [ "$FAILED" = "1" ] && [ "${FORCE:-0}" != "1" ]; then
    echo "СТОП. Разберитесь с конфликтами (или осознанно: FORCE=1 ./import-prod.sh ...)"
    exit 1
fi
echo "  конфликтов нет"

echo "== 2/3 Страховочный дамп =="
TABLES=$(grep -oP '(?<=DROP TABLE IF EXISTS `)[^`]+' "$TMP/owned.sql" | tr '\n' ' ')
BACKUP="backup-before-import-$(date +%F-%H%M).sql.gz"
"${DUMP[@]}" $TABLES components process_names media | gzip > "$BACKUP"
echo "  сохранён: $BACKUP"

echo "== 3/3 Применение =="
"${MYSQL[@]}" < "$TMP/owned.sql"          && echo "  owned.sql — ok"
"${MYSQL[@]}" < "$TMP/shared-replace.sql" && echo "  shared-replace.sql — ok"
"${MYSQL[@]}" < "$TMP/media-new.sql"      && echo "  media-new.sql — ok"

echo ""
echo "База готова. ОСТАЛОСЬ: распаковать файлы медиа в storage/app/public:"
echo "  tar xzf files.tar.gz -C /путь/к/avia/storage/app/public"
echo "(архив лежит внутри $ARCHIVE)"

#!/usr/bin/env bash
# ------------------------------------------------------------------
# export-manual.sh — запускается на флешке (Xubuntu) в конце рабочей
# сессии. Собирает ОДИН архив для переноса на прод:
#
#   trip-manual-ГГГГ-ММ-ДД.tar.gz
#     ├── owned.sql          — "свои" таблицы целиком (DROP+CREATE+INSERT)
#     ├── shared-replace.sql — components/process_names как REPLACE INTO
#     │                        (новые строки добавятся, правки применятся,
#     │                         продовые строки не пострадают)
#     ├── media-new.sql      — только новые media (id >= 10 000 000)
#     ├── files.tar.gz       — папки storage/app/public/<id> новых media
#     └── baseline.tsv       — предохранитель для импорта
#
# Отправьте архив себе в Telegram (Избранное).
# ------------------------------------------------------------------
set -euo pipefail
cd "$(dirname "$0")"

DB_NAME="${DB_NAME:-avia}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
[ -f trip-offset.txt ] || { echo "ОШИБКА: нет trip-offset.txt — сначала запустите prepare-trip.sh"; exit 1; }
OFFSET=$(cat trip-offset.txt)
ROOT="$(cd ../.. && pwd)"                 # корень проекта avia
STORAGE="$ROOT/storage/app/public"
STAMP=$(date +%F)
OUT_DIR="${OUT_DIR:-$HOME}"
OUT="$OUT_DIR/trip-manual-$STAMP.tar.gz"

[ -f baseline.tsv ] || { echo "ОШИБКА: нет baseline.tsv — сначала запустите prepare-trip.sh"; exit 1; }

DUMP=(mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} --single-transaction "$DB_NAME")
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

echo "== 1/4 Свои таблицы =="
"${DUMP[@]}" $(tr '\n' ' ' < tables-owned.txt) > "$TMP/owned.sql"

echo "== 2/4 Совместные таблицы (REPLACE) =="
"${DUMP[@]}" --no-create-info --replace $(tr '\n' ' ' < tables-shared.txt) > "$TMP/shared-replace.sql"

echo "== 3/4 Новые media (id >= $OFFSET) =="
"${DUMP[@]}" --no-create-info --insert-ignore --where="id >= $OFFSET" media > "$TMP/media-new.sql"

echo "== 4/4 Файлы новых media =="
NEW_DIRS=$(find "$STORAGE" -maxdepth 1 -type d -regextype posix-extended -regex '.*/[0-9]+' \
    | awk -F/ -v off="$OFFSET" '$NF+0 >= off {print $NF}')
if [ -n "$NEW_DIRS" ]; then
    (cd "$STORAGE" && tar czf "$TMP/files.tar.gz" $NEW_DIRS)
    echo "  файлов-папок: $(echo "$NEW_DIRS" | wc -l)"
else
    tar czf "$TMP/files.tar.gz" --files-from /dev/null
    echo "  новых файлов нет"
fi

cp baseline.tsv "$TMP/"
tar czf "$OUT" -C "$TMP" owned.sql shared-replace.sql media-new.sql files.tar.gz baseline.tsv
echo ""
echo "Готово: $OUT ($(du -h "$OUT" | cut -f1))"
echo "Отправьте этот файл себе в Telegram."

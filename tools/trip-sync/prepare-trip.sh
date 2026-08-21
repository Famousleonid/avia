#!/usr/bin/env bash
# ------------------------------------------------------------------
# prepare-trip.sh — запускается на флешке (Xubuntu) КАЖДЫЙ РАЗ после
# заливки свежего дампа прода (перед отъездом и после каждого
# refresh-local.sh), ПЕРЕД началом локальной работы.
#
# Что делает:
#  1. Вычисляет следующую свободную "полосу" id (шаг 10 000 000) и
#     сдвигает туда AUTO_INCREMENT совместных таблиц (media,
#     components, process_names). Всё созданное локально получает id
#     из этой полосы и не пересечётся с продом — сколько бы циклов
#     прод<->локалка ни было. Полоса записывается в trip-offset.txt.
#  2. Сохраняет baseline.tsv — снимок MAX(id) "своих" таблиц.
#     При импорте на прод он служит предохранителем: если на проде
#     кто-то добавил строки в эти таблицы, импорт остановится.
#
# Повторный запуск БЕЗ обновления базы с прода заблокирован
# (иначе экспорт потерял бы уже введённое): используйте refresh-local.sh.
# ------------------------------------------------------------------
set -euo pipefail
cd "$(dirname "$0")"

DB_NAME="${DB_NAME:-avia}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
BAND=10000000

MYSQL=(mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME")

if [ -f trip-offset.txt ]; then
    echo "СТОП: trip-offset.txt уже существует (полоса $(cat trip-offset.txt))."
    echo "Если вы только что обновили базу свежим дампом прода — запустите refresh-local.sh,"
    echo "либо удалите trip-offset.txt вручную и повторите."
    exit 1
fi

echo "== Выбор новой полосы id =="
gmax=0
for t in media $(cat tables-shared.txt); do
    cur=$("${MYSQL[@]}" -N -e "SELECT COALESCE(MAX(id),0) FROM \`$t\`")
    [ "$cur" -gt "$gmax" ] && gmax=$cur
done
OFFSET=$(( (gmax / BAND + 1) * BAND ))
echo "  максимум занятых id: $gmax  ->  новая полоса: $OFFSET"

for t in media $(cat tables-shared.txt); do
    "${MYSQL[@]}" -e "ALTER TABLE \`$t\` AUTO_INCREMENT=$OFFSET"
    echo "  $t: AUTO_INCREMENT -> $OFFSET"
done
echo "$OFFSET" > trip-offset.txt

echo "== Снимок baseline (MAX id своих таблиц) =="
: > baseline.tsv
while read -r t; do
    [ -z "$t" ] && continue
    m=$("${MYSQL[@]}" -N -e "SELECT COALESCE(MAX(id),0) FROM \`$t\`")
    printf '%s\t%s\n' "$t" "$m" >> baseline.tsv
done < tables-owned.txt
echo "baseline.tsv записан ($(wc -l < baseline.tsv) таблиц), дата: $(date +%F)"
echo "Готово. Можно работать локально."

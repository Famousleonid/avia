# Перенос Work Stage на production

Сверка от 20/Nov/2026 сделана от Git `66500fdd` и по фактической базе production. На сервере MariaDB 10.11. На production было 17 задач и 5 General Tasks. Все 300 подтверждённых **Completed** уже имеют `workorders.done_at`. Из них 299 заказов видны в обычном списке, один удалён из списка. Для этой установки дополнительное заполнение `done_at` не требуется.

## Что получает production

Скопировать согласованным комплектом `/app`, `/config`, `/database`, `/public`, `/resources`, `/routes`.

Миграции добавляют таблицу Work Stage, ключи этапов и признаки порядка и архива. Поля `workorders.approve_at`, `workorders.done_at` и `workorders.done_user_id` уже есть.

SQL переводит прежние задачи в историю и добавляет четыре новых определения. Он сохраняет исходные ID, названия и принадлежность исторических задач. Production-задачи **WO Submitted to inspection** и **Post Disassembly inspection** на этом шаге используют прежние ID; их разделяет следующая миграция от 21 ноября. Внутренняя задача **Approved** остаётся связанной с существующим механизмом записи даты. Связанные записи `mains`, история изменений и поля заказов остаются на месте. По уточнению от 21 ноября архивные даты и отметки не показываются в Main ни одной роли, в том числе по старой ссылке. Они сохраняются в базе и System Log.

Новый активный справочник доступен для дальнейшей работы по новым и уже открытым WO. Для ранее записанных задач остаётся история. Перенос сохраняет глобальное завершение ранее закрытых заказов.

Уточнение 21/Nov/2026: после этого SQL выполнить миграцию `2026_11_21_120000_retire_reused_legacy_disassembly_tasks`. Она архивирует повторно использованные старые определения `3. WO Submitted to inspection` и `Post Disassembly inspection` и создаёт отдельные активные задачи для новых дат. Все прежние записи `mains`, даты, исполнители и `ignore_row` сохраняются с историческими ID. На установке, где уже используются новые определения без старого числового префикса, изменений нет. Миграция намеренно отказывает на старом справочнике без Wait records / In Quotation: сначала требуется приведённый ниже SQL. Автоматический rollback не объединяет поколения задач, поскольку новые ID уже могут иметь записи.

```bash
php artisan migrate --force --path=database/migrations/2026_11_21_120000_retire_reused_legacy_disassembly_tasks.php
```

## Порядок установки

1. Сохранить резервную копию production-базы и заменяемого кода. На время установки приостановить запись в приложение.
2. Скопировать комплект папок. Из корня приложения выполнить только миграции этого релиза:

   ```bash
   php artisan migrate --force \
     --path=database/migrations/2026_11_20_180000_create_work_stages_and_mark_technician_stages.php \
     --path=database/migrations/2026_11_20_190000_add_order_and_archive_to_tasks.php \
     --path=database/migrations/2026_11_20_200000_add_catalog_removed_at_to_general_tasks.php \
     --path=database/migrations/2026_11_20_210000_link_work_stages_to_workflow.php
   ```

3. Запустить [SQL переноса справочника](../database/scripts/2026-11-20-work-stage-production.sql) в одном подключении к production-базе. При запуске из MySQL/MariaDB CLI использовать обычный режим остановки при ошибке. Скрипт проверяет исходные определения и итог в одной транзакции. Ожидаемый итог `APPLIED`. При `SKIPPED_BASELINE_MISMATCH` или `ROLLED_BACK_POSTCHECK` сверить справочник и состояние миграций перед открытием приложения.
4. Сверить число закрытых по `done_at`, число строк `mains` и отсутствие повреждённых ссылок:

   ```sql
   SELECT COUNT(*) AS closed_total,
          SUM(deleted_at IS NULL AND is_draft = 0) AS closed_in_list
   FROM workorders WHERE done_at IS NOT NULL;

   SELECT COUNT(*) AS total_main_rows,
          SUM(t.id IS NULL OR g.id IS NULL) AS broken_task_links
   FROM mains m
   LEFT JOIN tasks t ON t.id = m.task_id
   LEFT JOIN general_tasks g ON g.id = m.general_task_id;
   ```

   На момент сверки было 300 закрытых всего, 299 в списке и 5 057 строк `mains`. Сравнить результаты до и после переноса по фактическим значениям на момент установки.
5. Проверить старый закрытый WO, один открытый WO и фильтр **WO active**. Старая ссылка на архивную дату не должна раскрывать историю в Main. У Technician и Team Leader доступны две предусмотренные даты. У Manager и Admin в **Stages** виден текстовый этап. **OVH in Process** определяется датой `approve_at`. После проверки возобновить работу приложения.

## Откат

До возобновления записи вернуть прежний комплект кода и резервную копию базы, если перенос уже завершился или нужно отменить изменения схемы. После возобновления работы точку восстановления согласовать отдельно, чтобы сохранить изменения, внесённые после установки.

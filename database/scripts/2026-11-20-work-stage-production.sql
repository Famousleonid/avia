-- AVIA Work Stage catalogue cutover for the production baseline inspected on 20/Nov/2026.
-- Apply Laravel migrations 2026_11_20_180000 through 2026_11_20_210000 first.
-- Run in one connection while application writes are paused. Review the final result.
-- The only persistent tables changed by this script are general_tasks and tasks.
-- Historical task IDs, names, group assignments, mains, audits and WO dates are preserved.

DROP TEMPORARY TABLE IF EXISTS avia_stage_old_tasks;
CREATE TEMPORARY TABLE avia_stage_old_tasks (
    id BIGINT UNSIGNED PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    has_start TINYINT NOT NULL
) ENGINE=InnoDB;

INSERT INTO avia_stage_old_tasks (id, group_id, task_name, has_start) VALUES
    (1, 1, '2. Disassembly', 1),
    (29, 1, '3. WO Submitted to inspection', 0),
    (35, 1, '1. Preliminary Test', 0),
    (41, 1, 'Post Disassembly inspection', 0),
    (42, 1, 'WO Submitted for Quote', 0),
    (10, 2, 'Approved', 0),
    (43, 2, 'Quote Sent to Customer', 0),
    (31, 3, '2. Waiting parts', 1),
    (33, 3, '3. Bushing (Waiting, Machining, NDT. Cad. Passivation. etc.)', 1),
    (34, 3, '1. Assembly', 1),
    (37, 3, '4. Spring Test', 1),
    (23, 5, '1. Final Test', 0),
    (40, 5, '2. WO Submitted for Final Inspection', 0),
    (44, 5, 'Final inspection', 0),
    (28, 6, 'Completed', 0),
    (45, 6, '1. WO invoiced to Customer', 0),
    (46, 6, '2. WO Paid by Customer', 0);

START TRANSACTION;

SET @avia_stage_group_signature := (
    SELECT COUNT(*) FROM general_tasks
    WHERE catalog_removed_at IS NULL AND (
        (id = 1 AND BINARY name = 'Disassembly' AND technician_stage = 'disassembly')
        OR (id = 2 AND BINARY name = 'Approved' AND technician_stage IS NULL)
        OR (id = 3 AND BINARY name = 'Assembly' AND technician_stage = 'assembly')
        OR (id = 5 AND BINARY name = 'Final Test' AND technician_stage IS NULL)
        OR (id = 6 AND BINARY name = 'Complete' AND technician_stage IS NULL)
    )
);
SET @avia_stage_task_signature := (
    SELECT COUNT(*) FROM avia_stage_old_tasks expected
    JOIN tasks t ON t.id = expected.id
        AND t.general_task_id = expected.group_id
        AND BINARY t.name = BINARY expected.task_name
        AND t.task_has_start_date = expected.has_start
    WHERE t.archived_at IS NULL
);
SET @avia_stage_catalog_signature := (
    SELECT COUNT(*) FROM work_stages
    WHERE (workflow_key, name, sort_order) IN (
        ('disassembly', 'Disassembly', 10),
        ('wait_records', 'Wait Records', 20),
        ('in_quotation', 'In Quotation', 30),
        ('wait_approval', 'Wait Approval', 40),
        ('ovh_in_process', 'OVH in Process', 50),
        ('final_assembly_test', 'Final Assembly & Test', 60),
        ('final_inspection', 'Final Inspection', 70)
    )
);
SET @avia_stage_apply := (
    @avia_stage_group_signature = 5 AND (SELECT COUNT(*) FROM general_tasks) = 5
    AND @avia_stage_task_signature = 17 AND (SELECT COUNT(*) FROM tasks) = 17
    AND @avia_stage_catalog_signature = 7 AND (SELECT COUNT(*) FROM work_stages) = 7
);
SET @avia_stage_cutover := CURRENT_TIMESTAMP;

-- Preserve the old groups and their names. Approved remains an internal date mirror.
UPDATE general_tasks
SET catalog_removed_at = @avia_stage_cutover, technician_stage = NULL
WHERE @avia_stage_apply = 1 AND id IN (2, 3, 5, 6);

INSERT INTO general_tasks (name, sort_order, has_start_date, technician_stage)
SELECT 'Assembly & Test', 2, 1, 'assembly'
WHERE @avia_stage_apply = 1;
SET @avia_new_assembly_group := (
    SELECT id FROM general_tasks WHERE technician_stage = 'assembly' AND BINARY name = 'Assembly & Test'
);

-- WO Submitted to inspection and Post Disassembly inspection are the same milestones in production.
-- Retain their original names and task IDs. Keep Approved available to its existing writer.
UPDATE tasks t JOIN avia_stage_old_tasks expected ON expected.id = t.id
SET t.archived_at = @avia_stage_cutover
WHERE @avia_stage_apply = 1 AND t.id NOT IN (10, 29, 41);

UPDATE tasks SET sort_order = CASE id WHEN 29 THEN 10 WHEN 41 THEN 20 END
WHERE @avia_stage_apply = 1 AND id IN (29, 41);

INSERT INTO tasks (general_task_id, name, task_has_start_date, sort_order)
SELECT 1, 'Wait records', 0, 30 WHERE @avia_stage_apply = 1
UNION ALL
SELECT 1, 'In Quotation', 0, 40 WHERE @avia_stage_apply = 1
UNION ALL
SELECT @avia_new_assembly_group, 'WO Submitted for Final Inspection', 0, 10
WHERE @avia_stage_apply = 1 AND @avia_new_assembly_group IS NOT NULL
UNION ALL
SELECT @avia_new_assembly_group, 'Final Inspection', 0, 20
WHERE @avia_stage_apply = 1 AND @avia_new_assembly_group IS NOT NULL;

SET @avia_old_definitions_preserved := (
    SELECT COUNT(*) FROM avia_stage_old_tasks expected
    JOIN tasks t ON t.id = expected.id
        AND t.general_task_id = expected.group_id
        AND BINARY t.name = BINARY expected.task_name
        AND t.task_has_start_date = expected.has_start
    WHERE (t.id IN (10, 29, 41) AND t.archived_at IS NULL)
       OR (t.id NOT IN (10, 29, 41) AND t.archived_at IS NOT NULL)
);
SELECT COUNT(*), SUM(catalog_removed_at IS NULL),
       SUM(catalog_removed_at IS NOT NULL AND technician_stage IS NULL)
INTO @avia_groups_after, @avia_active_groups_after, @avia_archived_groups_after
FROM general_tasks;

SET @avia_new_tasks_verified := (
    SELECT COUNT(*) FROM tasks t JOIN general_tasks g ON g.id = t.general_task_id
    WHERE t.archived_at IS NULL AND g.catalog_removed_at IS NULL AND (
        (g.technician_stage = 'disassembly' AND (
            (t.id = 29 AND t.sort_order = 10) OR (t.id = 41 AND t.sort_order = 20)
            OR (BINARY t.name = 'Wait records' AND t.sort_order = 30)
            OR (BINARY t.name = 'In Quotation' AND t.sort_order = 40)
        )) OR (g.id = @avia_new_assembly_group AND (
            (BINARY t.name = 'WO Submitted for Final Inspection' AND t.sort_order = 10)
            OR (BINARY t.name = 'Final Inspection' AND t.sort_order = 20)
        ))
    )
);
SET @avia_stage_applied := (
    @avia_stage_apply = 1 AND @avia_groups_after = 6
    AND @avia_active_groups_after = 2 AND @avia_archived_groups_after = 4
    AND @avia_new_assembly_group IS NOT NULL AND @avia_old_definitions_preserved = 17
    AND (SELECT COUNT(*) FROM tasks) = 21 AND @avia_new_tasks_verified = 6
);
SET @avia_stage_transaction_end := IF(@avia_stage_applied = 1, 'COMMIT', 'ROLLBACK');
PREPARE avia_stage_end FROM @avia_stage_transaction_end;
EXECUTE avia_stage_end;
DEALLOCATE PREPARE avia_stage_end;

SELECT CASE WHEN @avia_stage_applied = 1 THEN 'APPLIED'
            WHEN @avia_stage_apply = 1 THEN 'ROLLED_BACK_POSTCHECK'
            ELSE 'SKIPPED_BASELINE_MISMATCH'
       END AS result;

SELECT g.name AS general_task, t.id AS task_id, t.name AS task, t.sort_order
FROM general_tasks g JOIN tasks t ON t.general_task_id = g.id
WHERE g.catalog_removed_at IS NULL AND t.archived_at IS NULL
ORDER BY g.sort_order, g.id, t.sort_order, t.id;

DROP TEMPORARY TABLE avia_stage_old_tasks;

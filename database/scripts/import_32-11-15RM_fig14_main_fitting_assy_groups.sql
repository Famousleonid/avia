-- Manual 32-11-15RM - Figure 14 MAIN FITTING ASSY groups.
-- Source: IPL Figure 14, pages 10167-10173, revision Aug 31/22.
--
-- This script is authoritative for the MPG-M78-F14-* codes below and is safe
-- to run more than once. It does not delete existing groups or selections.
-- It also:
--   1. restores Figure 14 item 12, which is present in the supplied IPL but is
--      currently soft-deleted;
--   2. marks the 60A/70A families as bushings, as stated by the IPL;
--   3. assigns matching Unit defaults and backfills only legacy Workorders
--      whose scope_type is NULL. This includes W107952 -> ASSY item 1C.

SET @avia_manual_number := '32-11-15RM';
SET @avia_scopes := '["prl","ndt","cad","stress","paint"]';
SET @avia_manual_count := (
    SELECT COUNT(*)
    FROM manuals
    WHERE number = @avia_manual_number
);
SET @avia_assert_sql := IF(
    @avia_manual_count = 1,
    'SELECT 1 AS manual_ok',
    'SELECT * FROM AVIA_ABORT_EXPECTED_EXACTLY_ONE_MANUAL_32_11_15RM'
);
PREPARE avia_assert_stmt FROM @avia_assert_sql;
EXECUTE avia_assert_stmt;
DEALLOCATE PREPARE avia_assert_stmt;

SET @avia_manual_id := (
    SELECT id
    FROM manuals
    WHERE number = @avia_manual_number
    LIMIT 1
);

START TRANSACTION;

-- The supplied IPL contains item 14-12. Keep the existing component id and
-- restore the soft-deleted row instead of inserting a duplicate component.
UPDATE components
SET deleted_at = NULL,
    is_bush = 1,
    bush_ipl_num = '14-10',
    updated_at = CURRENT_TIMESTAMP
WHERE manual_id = @avia_manual_id
  AND ipl_num = '14-12'
  AND part_number = '2821-0102FS02';

DROP TEMPORARY TABLE IF EXISTS avia_fig14_group_defs;
CREATE TEMPORARY TABLE avia_fig14_group_defs (
    code VARCHAR(32) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    behavior VARCHAR(24) NOT NULL,
    type VARCHAR(32) NOT NULL,
    family_ipl VARCHAR(50) NULL
);

INSERT INTO avia_fig14_group_defs (code, name, behavior, type, family_ipl) VALUES
    ('MPG-M78-F14-B10', 'Bushing 14-10', 'choose_one', 'oversize', '14-10'),
    ('MPG-M78-F14-B20', 'Bushing Shoulder 14-20', 'choose_one', 'oversize', '14-20'),
    ('MPG-M78-F14-B30', 'Bushing 14-30', 'choose_one', 'oversize', '14-30'),
    ('MPG-M78-F14-B40', 'Bushing Shoulder 14-40', 'choose_one', 'oversize', '14-40'),
    ('MPG-M78-F14-B50', 'Bushing Shoulder 14-50', 'choose_one', 'oversize', '14-50'),
    ('MPG-M78-F14-B60A', 'Bushing Shoulder 14-60A', 'choose_one', 'oversize', '14-60A'),
    ('MPG-M78-F14-B60B', 'Bushing Shoulder 14-60B', 'choose_one', 'oversize', '14-60B'),
    ('MPG-M78-F14-B70A', 'Bushing Shoulder 14-70A', 'choose_one', 'oversize', '14-70A'),
    ('MPG-M78-F14-B70B', 'Bushing Shoulder 14-70B', 'choose_one', 'oversize', '14-70B'),
    ('MPG-M78-F14-B80', 'Bushing Shoulder 14-80', 'choose_one', 'oversize', '14-80'),
    ('MPG-M78-F14-B100', 'Bushing Shoulder 14-100', 'choose_one', 'oversize', '14-100'),
    ('MPG-M78-F14-B110', 'Bushing Shoulder 14-110', 'choose_one', 'oversize', '14-110'),
    ('MPG-M78-F14-B120', 'Bushing Shoulder 14-120', 'choose_one', 'oversize', '14-120'),
    ('MPG-M78-F14-B130', 'Bushing Shoulder 14-130', 'choose_one', 'oversize', '14-130'),
    ('MPG-M78-F14-B140', 'Bushing Shoulder 14-140', 'choose_one', 'oversize', '14-140'),
    ('MPG-M78-F14-B150', 'Bushing Shoulder 14-150', 'choose_one', 'oversize', '14-150'),
    ('MPG-M78-F14-B160', 'Bushing Shoulder 14-160', 'choose_one', 'oversize', '14-160'),
    ('MPG-M78-F14-B170', 'Bushing Shoulder 14-170', 'choose_one', 'oversize', '14-170'),
    ('MPG-M78-F14-PIN90', 'Pin 14-90', 'choose_one', 'alternative_pn', NULL),
    ('MPG-M78-F14-INSERT200', 'Sleeve Threaded Insert 14-200', 'choose_one', 'alternative_pn', NULL),
    ('MPG-M78-F14-ASSY-1A', '2821A0100-02', 'bundle', 'assy', NULL),
    ('MPG-M78-F14-ASSY-1B', '2822A0100-02', 'bundle', 'assy', NULL),
    ('MPG-M78-F14-ASSY-1C', '2821A0100-03', 'bundle', 'assy', NULL),
    ('MPG-M78-F14-ASSY-1D', '2822A0100-03', 'bundle', 'assy', NULL);

SET @avia_wrong_group_owner := (
    SELECT COUNT(*)
    FROM manual_part_groups AS g
    INNER JOIN avia_fig14_group_defs AS d ON d.code = g.code
    WHERE g.manual_id <> @avia_manual_id
);
SET @avia_assert_sql := IF(
    @avia_wrong_group_owner = 0,
    'SELECT 1 AS group_codes_ok',
    'SELECT * FROM AVIA_ABORT_FIG14_GROUP_CODE_BELONGS_TO_ANOTHER_MANUAL'
);
PREPARE avia_assert_stmt FROM @avia_assert_sql;
EXECUTE avia_assert_stmt;
DEALLOCATE PREPARE avia_assert_stmt;

DROP TEMPORARY TABLE IF EXISTS avia_fig14_option_defs;
CREATE TEMPORARY TABLE avia_fig14_option_defs (
    group_code VARCHAR(32) NOT NULL,
    component_ipl VARCHAR(50) NOT NULL,
    option_kind VARCHAR(24) NOT NULL,
    is_default TINYINT(1) NOT NULL,
    sort_order INT UNSIGNED NOT NULL,
    PRIMARY KEY (group_code, component_ipl)
);

INSERT INTO avia_fig14_option_defs
    (group_code, component_ipl, option_kind, is_default, sort_order)
VALUES
    ('MPG-M78-F14-B10', '14-10', 'original', 1, 0),
    ('MPG-M78-F14-B10', '14-11', 'oversize', 0, 1),
    ('MPG-M78-F14-B10', '14-12', 'oversize', 0, 2),
    ('MPG-M78-F14-B10', '14-13', 'oversize', 0, 3),
    ('MPG-M78-F14-B10', '14-14', 'oversize', 0, 4),
    ('MPG-M78-F14-B20', '14-20', 'original', 1, 0),
    ('MPG-M78-F14-B20', '14-21', 'oversize', 0, 1),
    ('MPG-M78-F14-B20', '14-22', 'oversize', 0, 2),
    ('MPG-M78-F14-B20', '14-23', 'oversize', 0, 3),
    ('MPG-M78-F14-B30', '14-30', 'original', 1, 0),
    ('MPG-M78-F14-B30', '14-31', 'oversize', 0, 1),
    ('MPG-M78-F14-B30', '14-32', 'oversize', 0, 2),
    ('MPG-M78-F14-B30', '14-33', 'oversize', 0, 3),
    ('MPG-M78-F14-B40', '14-40', 'original', 1, 0),
    ('MPG-M78-F14-B40', '14-41', 'oversize', 0, 1),
    ('MPG-M78-F14-B40', '14-42', 'oversize', 0, 2),
    ('MPG-M78-F14-B40', '14-43', 'oversize', 0, 3),
    ('MPG-M78-F14-B40', '14-44', 'oversize', 0, 4),
    ('MPG-M78-F14-B50', '14-50', 'original', 1, 0),
    ('MPG-M78-F14-B50', '14-51', 'oversize', 0, 1),
    ('MPG-M78-F14-B50', '14-52', 'oversize', 0, 2),
    ('MPG-M78-F14-B50', '14-53', 'oversize', 0, 3),
    ('MPG-M78-F14-B50', '14-54', 'oversize', 0, 4),
    ('MPG-M78-F14-B60A', '14-60A', 'original', 1, 0),
    ('MPG-M78-F14-B60A', '14-61A', 'oversize', 0, 1),
    ('MPG-M78-F14-B60A', '14-62A', 'oversize', 0, 2),
    ('MPG-M78-F14-B60A', '14-63A', 'oversize', 0, 3),
    ('MPG-M78-F14-B60B', '14-60B', 'original', 1, 0),
    ('MPG-M78-F14-B60B', '14-61B', 'oversize', 0, 1),
    ('MPG-M78-F14-B60B', '14-62B', 'oversize', 0, 2),
    ('MPG-M78-F14-B60B', '14-63B', 'oversize', 0, 3),
    ('MPG-M78-F14-B70A', '14-70A', 'original', 1, 0),
    ('MPG-M78-F14-B70A', '14-71A', 'oversize', 0, 1),
    ('MPG-M78-F14-B70A', '14-72A', 'oversize', 0, 2),
    ('MPG-M78-F14-B70A', '14-73A', 'oversize', 0, 3),
    ('MPG-M78-F14-B70B', '14-70B', 'original', 1, 0),
    ('MPG-M78-F14-B70B', '14-71B', 'oversize', 0, 1),
    ('MPG-M78-F14-B70B', '14-72B', 'oversize', 0, 2),
    ('MPG-M78-F14-B70B', '14-73B', 'oversize', 0, 3),
    ('MPG-M78-F14-B80', '14-80', 'original', 1, 0),
    ('MPG-M78-F14-B80', '14-81', 'oversize', 0, 1),
    ('MPG-M78-F14-B80', '14-82', 'oversize', 0, 2),
    ('MPG-M78-F14-B80', '14-83', 'oversize', 0, 3),
    ('MPG-M78-F14-B100', '14-100', 'original', 1, 0),
    ('MPG-M78-F14-B100', '14-101', 'oversize', 0, 1),
    ('MPG-M78-F14-B100', '14-102', 'oversize', 0, 2),
    ('MPG-M78-F14-B110', '14-110', 'original', 1, 0),
    ('MPG-M78-F14-B110', '14-111', 'oversize', 0, 1),
    ('MPG-M78-F14-B110', '14-112', 'oversize', 0, 2),
    ('MPG-M78-F14-B110', '14-113', 'oversize', 0, 3),
    ('MPG-M78-F14-B120', '14-120', 'original', 1, 0),
    ('MPG-M78-F14-B120', '14-121', 'oversize', 0, 1),
    ('MPG-M78-F14-B120', '14-122', 'oversize', 0, 2),
    ('MPG-M78-F14-B120', '14-123', 'oversize', 0, 3),
    ('MPG-M78-F14-B120', '14-124', 'oversize', 0, 4),
    ('MPG-M78-F14-B130', '14-130', 'original', 1, 0),
    ('MPG-M78-F14-B130', '14-131', 'oversize', 0, 1),
    ('MPG-M78-F14-B130', '14-132', 'oversize', 0, 2),
    ('MPG-M78-F14-B130', '14-133', 'oversize', 0, 3),
    ('MPG-M78-F14-B130', '14-134', 'oversize', 0, 4),
    ('MPG-M78-F14-B140', '14-140', 'original', 1, 0),
    ('MPG-M78-F14-B140', '14-141', 'oversize', 0, 1),
    ('MPG-M78-F14-B140', '14-142', 'oversize', 0, 2),
    ('MPG-M78-F14-B140', '14-143', 'oversize', 0, 3),
    ('MPG-M78-F14-B140', '14-144', 'oversize', 0, 4),
    ('MPG-M78-F14-B150', '14-150', 'original', 1, 0),
    ('MPG-M78-F14-B150', '14-151', 'oversize', 0, 1),
    ('MPG-M78-F14-B150', '14-152', 'oversize', 0, 2),
    ('MPG-M78-F14-B150', '14-153', 'oversize', 0, 3),
    ('MPG-M78-F14-B160', '14-160', 'original', 1, 0),
    ('MPG-M78-F14-B160', '14-161', 'oversize', 0, 1),
    ('MPG-M78-F14-B160', '14-162', 'oversize', 0, 2),
    ('MPG-M78-F14-B160', '14-163', 'oversize', 0, 3),
    ('MPG-M78-F14-B170', '14-170', 'original', 1, 0),
    ('MPG-M78-F14-B170', '14-171', 'oversize', 0, 1),
    ('MPG-M78-F14-B170', '14-172', 'oversize', 0, 2),
    ('MPG-M78-F14-B170', '14-173', 'oversize', 0, 3),
    ('MPG-M78-F14-PIN90', '14-90', 'alternate', 1, 0),
    ('MPG-M78-F14-PIN90', '14-91', 'alternate', 0, 1),
    ('MPG-M78-F14-PIN90', '14-92', 'alternate', 0, 2),
    ('MPG-M78-F14-PIN90', '14-93', 'alternate', 0, 3),
    ('MPG-M78-F14-INSERT200', '14-200', 'alternate', 1, 0),
    ('MPG-M78-F14-INSERT200', '14-201', 'alternate', 0, 1),
    ('MPG-M78-F14-INSERT200', '14-202', 'alternate', 0, 2),
    ('MPG-M78-F14-ASSY-1A', '14-1A', 'assy', 1, 0),
    ('MPG-M78-F14-ASSY-1B', '14-1B', 'assy', 1, 0),
    ('MPG-M78-F14-ASSY-1C', '14-1C', 'assy', 1, 0),
    ('MPG-M78-F14-ASSY-1D', '14-1D', 'assy', 1, 0);

-- The Figure 14 A-families are bushings in the supplied IPL. Normalize every
-- expected oversize option through the group definition, without changing any
-- unrelated component flags.
UPDATE components AS c
INNER JOIN avia_fig14_option_defs AS o ON o.component_ipl = c.ipl_num
INNER JOIN avia_fig14_group_defs AS g
    ON g.code = o.group_code
   AND g.type = 'oversize'
SET c.is_bush = 1,
    c.bush_ipl_num = g.family_ipl,
    c.updated_at = CURRENT_TIMESTAMP
WHERE c.manual_id = @avia_manual_id
  AND c.deleted_at IS NULL;

SET @avia_missing_components := (
    SELECT COUNT(*)
    FROM avia_fig14_option_defs AS o
    LEFT JOIN components AS c
        ON c.manual_id = @avia_manual_id
       AND c.ipl_num = o.component_ipl
       AND c.deleted_at IS NULL
    WHERE c.id IS NULL
);
SET @avia_ambiguous_components := (
    SELECT COUNT(*)
    FROM (
        SELECT o.group_code, o.component_ipl
        FROM avia_fig14_option_defs AS o
        INNER JOIN components AS c
            ON c.manual_id = @avia_manual_id
           AND c.ipl_num = o.component_ipl
           AND c.deleted_at IS NULL
        GROUP BY o.group_code, o.component_ipl
        HAVING COUNT(c.id) <> 1
    ) AS ambiguous
);
SET @avia_assert_sql := IF(
    @avia_missing_components = 0 AND @avia_ambiguous_components = 0,
    'SELECT 1 AS figure14_components_ok',
    'SELECT * FROM AVIA_ABORT_MISSING_OR_AMBIGUOUS_FIGURE14_COMPONENT'
);
PREPARE avia_assert_stmt FROM @avia_assert_sql;
EXECUTE avia_assert_stmt;
DEALLOCATE PREPARE avia_assert_stmt;

INSERT INTO manual_part_groups
    (manual_id, manual_service_bulletin_id, code, name, behavior, type,
     applies_to, notes, created_by_user_id, created_at, updated_at, deleted_at)
SELECT @avia_manual_id, NULL, d.code, d.name, d.behavior, d.type,
       @avia_scopes,
       '32-11-15RM Figure 14 MAIN FITTING ASSY; IPL rev Aug 31/22',
       NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL
FROM avia_fig14_group_defs AS d
WHERE NOT EXISTS (
    SELECT 1
    FROM manual_part_groups AS existing_group
    WHERE existing_group.code = d.code
);

UPDATE manual_part_groups AS g
INNER JOIN avia_fig14_group_defs AS d ON d.code = g.code
SET g.manual_id = @avia_manual_id,
    g.manual_service_bulletin_id = NULL,
    g.name = d.name,
    g.behavior = d.behavior,
    g.type = d.type,
    g.applies_to = @avia_scopes,
    g.notes = '32-11-15RM Figure 14 MAIN FITTING ASSY; IPL rev Aug 31/22',
    g.updated_at = CURRENT_TIMESTAMP,
    g.deleted_at = NULL;

SET @avia_unexpected_active_options := (
    SELECT COUNT(*)
    FROM manual_part_group_options AS o
    INNER JOIN manual_part_groups AS g ON g.id = o.manual_part_group_id
    INNER JOIN avia_fig14_group_defs AS gd ON gd.code = g.code
    LEFT JOIN components AS c ON c.id = o.component_id
    LEFT JOIN avia_fig14_option_defs AS expected
        ON expected.group_code = g.code
       AND expected.component_ipl = c.ipl_num
    WHERE o.deleted_at IS NULL
      AND expected.group_code IS NULL
);
SET @avia_assert_sql := IF(
    @avia_unexpected_active_options = 0,
    'SELECT 1 AS existing_options_ok',
    'SELECT * FROM AVIA_ABORT_UNEXPECTED_ACTIVE_OPTION_IN_MANAGED_FIG14_GROUP'
);
PREPARE avia_assert_stmt FROM @avia_assert_sql;
EXECUTE avia_assert_stmt;
DEALLOCATE PREPARE avia_assert_stmt;

INSERT INTO manual_part_group_options
    (manual_part_group_id, component_id, part_number, ipl_num, label,
     option_kind, oversize_value, is_default, sort_order,
     created_at, updated_at, deleted_at)
SELECT g.id, c.id, c.part_number, c.ipl_num, NULL,
       d.option_kind, NULL, d.is_default, d.sort_order,
       CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL
FROM avia_fig14_option_defs AS d
INNER JOIN manual_part_groups AS g
    ON g.code = d.group_code
   AND g.manual_id = @avia_manual_id
   AND g.deleted_at IS NULL
INNER JOIN components AS c
    ON c.manual_id = @avia_manual_id
   AND c.ipl_num = d.component_ipl
   AND c.deleted_at IS NULL
WHERE NOT EXISTS (
    SELECT 1
    FROM manual_part_group_options AS existing_option
    WHERE existing_option.manual_part_group_id = g.id
      AND existing_option.component_id = c.id
);

UPDATE manual_part_group_options AS o
INNER JOIN manual_part_groups AS g
    ON g.id = o.manual_part_group_id
   AND g.manual_id = @avia_manual_id
INNER JOIN avia_fig14_option_defs AS d ON d.group_code = g.code
INNER JOIN components AS c
    ON c.id = o.component_id
   AND c.manual_id = @avia_manual_id
   AND c.ipl_num = d.component_ipl
SET o.part_number = c.part_number,
    o.ipl_num = c.ipl_num,
    o.label = NULL,
    o.option_kind = d.option_kind,
    o.oversize_value = NULL,
    o.is_default = d.is_default,
    o.sort_order = d.sort_order,
    o.updated_at = CURRENT_TIMESTAMP,
    o.deleted_at = NULL;

DROP TEMPORARY TABLE IF EXISTS avia_fig14_common_nested;
CREATE TEMPORARY TABLE avia_fig14_common_nested (
    child_group_code VARCHAR(32) NOT NULL PRIMARY KEY,
    qty INT UNSIGNED NOT NULL
);

INSERT INTO avia_fig14_common_nested (child_group_code, qty) VALUES
    ('MPG-M78-F14-B10', 2),
    ('MPG-M78-F14-B20', 1),
    ('MPG-M78-F14-B30', 1),
    ('MPG-M78-F14-B40', 1),
    ('MPG-M78-F14-B50', 1),
    ('MPG-M78-F14-B80', 4),
    ('MPG-M78-F14-PIN90', 2),
    ('MPG-M78-F14-B100', 2),
    ('MPG-M78-F14-B110', 2),
    ('MPG-M78-F14-B120', 1),
    ('MPG-M78-F14-B130', 1),
    ('MPG-M78-F14-B140', 1),
    ('MPG-M78-F14-B150', 1),
    ('MPG-M78-F14-B160', 1),
    ('MPG-M78-F14-B170', 2),
    ('MPG-M78-F14-INSERT200', 2);

DROP TEMPORARY TABLE IF EXISTS avia_fig14_assy_defs;
CREATE TEMPORARY TABLE avia_fig14_assy_defs (
    assy_group_code VARCHAR(32) NOT NULL PRIMARY KEY,
    assy_ipl VARCHAR(50) NOT NULL,
    variant_b60_code VARCHAR(32) NOT NULL,
    variant_b70_code VARCHAR(32) NOT NULL,
    main_fitting_ipl VARCHAR(50) NOT NULL
);

INSERT INTO avia_fig14_assy_defs
    (assy_group_code, assy_ipl, variant_b60_code, variant_b70_code, main_fitting_ipl)
VALUES
    ('MPG-M78-F14-ASSY-1A', '14-1A', 'MPG-M78-F14-B60A', 'MPG-M78-F14-B70A', '14-210A'),
    ('MPG-M78-F14-ASSY-1B', '14-1B', 'MPG-M78-F14-B60A', 'MPG-M78-F14-B70A', '14-210B'),
    ('MPG-M78-F14-ASSY-1C', '14-1C', 'MPG-M78-F14-B60B', 'MPG-M78-F14-B70B', '14-210A'),
    ('MPG-M78-F14-ASSY-1D', '14-1D', 'MPG-M78-F14-B60B', 'MPG-M78-F14-B70B', '14-210B');

DROP TEMPORARY TABLE IF EXISTS avia_fig14_nested_coverage_defs;
CREATE TEMPORARY TABLE avia_fig14_nested_coverage_defs (
    assy_group_code VARCHAR(32) NOT NULL,
    child_group_code VARCHAR(32) NOT NULL,
    qty INT UNSIGNED NOT NULL,
    PRIMARY KEY (assy_group_code, child_group_code)
);

INSERT INTO avia_fig14_nested_coverage_defs
    (assy_group_code, child_group_code, qty)
SELECT a.assy_group_code, c.child_group_code, c.qty
FROM avia_fig14_assy_defs AS a
CROSS JOIN avia_fig14_common_nested AS c;

INSERT INTO avia_fig14_nested_coverage_defs
    (assy_group_code, child_group_code, qty)
SELECT assy_group_code, variant_b60_code, 1
FROM avia_fig14_assy_defs;

INSERT INTO avia_fig14_nested_coverage_defs
    (assy_group_code, child_group_code, qty)
SELECT assy_group_code, variant_b70_code, 1
FROM avia_fig14_assy_defs;

DROP TEMPORARY TABLE IF EXISTS avia_fig14_direct_coverage_defs;
CREATE TEMPORARY TABLE avia_fig14_direct_coverage_defs (
    assy_group_code VARCHAR(32) NOT NULL,
    component_ipl VARCHAR(50) NOT NULL,
    qty INT UNSIGNED NOT NULL,
    PRIMARY KEY (assy_group_code, component_ipl)
);

-- Item 5 is AR in the IPL; the existing component master carries the approved
-- maximum quantity 2, so the ASSY coverage uses 2 without changing master qty.
INSERT INTO avia_fig14_direct_coverage_defs
    (assy_group_code, component_ipl, qty)
SELECT assy_group_code, assy_ipl, 1
FROM avia_fig14_assy_defs;

INSERT INTO avia_fig14_direct_coverage_defs
    (assy_group_code, component_ipl, qty)
SELECT assy_group_code, '14-5', 2
FROM avia_fig14_assy_defs;

INSERT INTO avia_fig14_direct_coverage_defs
    (assy_group_code, component_ipl, qty)
SELECT assy_group_code, '14-180B', 2
FROM avia_fig14_assy_defs;

INSERT INTO avia_fig14_direct_coverage_defs
    (assy_group_code, component_ipl, qty)
SELECT assy_group_code, '14-190', 2
FROM avia_fig14_assy_defs;

INSERT INTO avia_fig14_direct_coverage_defs
    (assy_group_code, component_ipl, qty)
SELECT assy_group_code, main_fitting_ipl, 1
FROM avia_fig14_assy_defs;

SET @avia_missing_direct_components := (
    SELECT COUNT(*)
    FROM avia_fig14_direct_coverage_defs AS d
    LEFT JOIN components AS c
        ON c.manual_id = @avia_manual_id
       AND c.ipl_num = d.component_ipl
       AND c.deleted_at IS NULL
    WHERE c.id IS NULL
);
SET @avia_missing_nested_options := (
    SELECT COUNT(*)
    FROM avia_fig14_nested_coverage_defs AS d
    LEFT JOIN manual_part_groups AS child_group
        ON child_group.manual_id = @avia_manual_id
       AND child_group.code = d.child_group_code
       AND child_group.deleted_at IS NULL
    LEFT JOIN manual_part_group_options AS child_option
        ON child_option.manual_part_group_id = child_group.id
       AND child_option.is_default = 1
       AND child_option.deleted_at IS NULL
    WHERE child_option.id IS NULL
);
SET @avia_assert_sql := IF(
    @avia_missing_direct_components = 0 AND @avia_missing_nested_options = 0,
    'SELECT 1 AS coverage_targets_ok',
    'SELECT * FROM AVIA_ABORT_MISSING_FIG14_COVERAGE_TARGET'
);
PREPARE avia_assert_stmt FROM @avia_assert_sql;
EXECUTE avia_assert_stmt;
DEALLOCATE PREPARE avia_assert_stmt;

INSERT INTO manual_part_group_coverages
    (manual_part_group_option_id, component_id,
     covered_manual_part_group_option_id, legacy_component_assembly_id,
     qty, applies_to, created_at, updated_at)
SELECT assy_option.id, component.id, NULL, NULL,
       d.qty, @avia_scopes, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM avia_fig14_direct_coverage_defs AS d
INNER JOIN manual_part_groups AS assy_group
    ON assy_group.manual_id = @avia_manual_id
   AND assy_group.code = d.assy_group_code
   AND assy_group.deleted_at IS NULL
INNER JOIN manual_part_group_options AS assy_option
    ON assy_option.manual_part_group_id = assy_group.id
   AND assy_option.is_default = 1
   AND assy_option.deleted_at IS NULL
INNER JOIN components AS component
    ON component.manual_id = @avia_manual_id
   AND component.ipl_num = d.component_ipl
   AND component.deleted_at IS NULL
ON DUPLICATE KEY UPDATE
    qty = VALUES(qty),
    applies_to = VALUES(applies_to),
    updated_at = VALUES(updated_at);

INSERT INTO manual_part_group_coverages
    (manual_part_group_option_id, component_id,
     covered_manual_part_group_option_id, legacy_component_assembly_id,
     qty, applies_to, created_at, updated_at)
SELECT assy_option.id, NULL, child_option.id, NULL,
       d.qty, @avia_scopes, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM avia_fig14_nested_coverage_defs AS d
INNER JOIN manual_part_groups AS assy_group
    ON assy_group.manual_id = @avia_manual_id
   AND assy_group.code = d.assy_group_code
   AND assy_group.deleted_at IS NULL
INNER JOIN manual_part_group_options AS assy_option
    ON assy_option.manual_part_group_id = assy_group.id
   AND assy_option.is_default = 1
   AND assy_option.deleted_at IS NULL
INNER JOIN manual_part_groups AS child_group
    ON child_group.manual_id = @avia_manual_id
   AND child_group.code = d.child_group_code
   AND child_group.deleted_at IS NULL
INNER JOIN manual_part_group_options AS child_option
    ON child_option.manual_part_group_id = child_group.id
   AND child_option.is_default = 1
   AND child_option.deleted_at IS NULL
ON DUPLICATE KEY UPDATE
    qty = VALUES(qty),
    applies_to = VALUES(applies_to),
    updated_at = VALUES(updated_at);

-- Configure matching Unit records so future Workorders snapshot the correct
-- Figure 14 ASSY scope instead of full_unit.
UPDATE units AS u
INNER JOIN avia_fig14_assy_defs AS d
INNER JOIN manual_part_groups AS g
    ON g.manual_id = @avia_manual_id
   AND g.code = d.assy_group_code
   AND g.deleted_at IS NULL
INNER JOIN manual_part_group_options AS o
    ON o.manual_part_group_id = g.id
   AND o.is_default = 1
   AND o.deleted_at IS NULL
SET u.default_scope_type = 'part_group_option',
    u.default_scope_component_id = NULL,
    u.default_scope_part_group_option_id = o.id,
    u.updated_at = CURRENT_TIMESTAMP
WHERE u.manual_id = @avia_manual_id
  AND u.part_number = o.part_number;

-- Backfill only legacy Main Fitting Workorders. Existing explicit scopes are
-- never overwritten. W107952 is matched to item 1C by Unit P/N 2821A0100-03.
UPDATE workorders AS w
INNER JOIN units AS u
    ON u.id = w.unit_id
   AND u.manual_id = @avia_manual_id
INNER JOIN avia_fig14_assy_defs AS d
INNER JOIN manual_part_groups AS g
    ON g.manual_id = @avia_manual_id
   AND g.code = d.assy_group_code
   AND g.deleted_at IS NULL
INNER JOIN manual_part_group_options AS o
    ON o.manual_part_group_id = g.id
   AND o.is_default = 1
   AND o.deleted_at IS NULL
SET w.scope_type = 'part_group_option',
    w.scope_component_id = NULL,
    w.scope_part_group_option_id = o.id,
    w.updated_at = CURRENT_TIMESTAMP
WHERE w.scope_type IS NULL
  AND u.part_number = o.part_number;

SET @avia_assy_group_count := (
    SELECT COUNT(*)
    FROM manual_part_groups AS g
    INNER JOIN avia_fig14_assy_defs AS d ON d.assy_group_code = g.code
    WHERE g.manual_id = @avia_manual_id
      AND g.type = 'assy'
      AND g.behavior = 'bundle'
      AND g.deleted_at IS NULL
);
SET @avia_assy_option_count := (
    SELECT COUNT(*)
    FROM manual_part_group_options AS o
    INNER JOIN manual_part_groups AS g ON g.id = o.manual_part_group_id
    INNER JOIN avia_fig14_assy_defs AS d ON d.assy_group_code = g.code
    WHERE o.is_default = 1
      AND o.deleted_at IS NULL
);
SET @avia_assert_sql := IF(
    @avia_assy_group_count = 4 AND @avia_assy_option_count = 4,
    'SELECT 1 AS assy_groups_ok',
    'SELECT * FROM AVIA_ABORT_EXPECTED_FOUR_FIGURE14_ASSY_GROUPS'
);
PREPARE avia_assert_stmt FROM @avia_assert_sql;
EXECUTE avia_assert_stmt;
DEALLOCATE PREPARE avia_assert_stmt;

COMMIT;

-- Verification output: four ASSY options, each with 23 coverage rows.
SELECT g.code,
       o.part_number,
       o.ipl_num,
       COUNT(cov.id) AS coverage_rows
FROM manual_part_groups AS g
INNER JOIN manual_part_group_options AS o
    ON o.manual_part_group_id = g.id
   AND o.deleted_at IS NULL
LEFT JOIN manual_part_group_coverages AS cov
    ON cov.manual_part_group_option_id = o.id
WHERE g.manual_id = @avia_manual_id
  AND g.code IN (
      'MPG-M78-F14-ASSY-1A',
      'MPG-M78-F14-ASSY-1B',
      'MPG-M78-F14-ASSY-1C',
      'MPG-M78-F14-ASSY-1D'
  )
  AND g.deleted_at IS NULL
GROUP BY g.code, o.part_number, o.ipl_num
ORDER BY g.code;

SELECT w.number,
       u.part_number AS unit_part_number,
       w.scope_type,
       g.code AS scope_group,
       o.ipl_num AS scope_ipl
FROM workorders AS w
INNER JOIN units AS u ON u.id = w.unit_id
LEFT JOIN manual_part_group_options AS o ON o.id = w.scope_part_group_option_id
LEFT JOIN manual_part_groups AS g ON g.id = o.manual_part_group_id
WHERE u.manual_id = @avia_manual_id
  AND u.part_number IN (
      '2821A0100-02',
      '2822A0100-02',
      '2821A0100-03',
      '2822A0100-03'
  )
ORDER BY w.number;

DROP TEMPORARY TABLE IF EXISTS avia_fig14_direct_coverage_defs;
DROP TEMPORARY TABLE IF EXISTS avia_fig14_nested_coverage_defs;
DROP TEMPORARY TABLE IF EXISTS avia_fig14_assy_defs;
DROP TEMPORARY TABLE IF EXISTS avia_fig14_common_nested;
DROP TEMPORARY TABLE IF EXISTS avia_fig14_option_defs;
DROP TEMPORARY TABLE IF EXISTS avia_fig14_group_defs;

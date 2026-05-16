-- Exported from local manual_service_bulletins on 2026-05-16 10:22:35
-- Idempotent import: matches manuals by manuals.number and skips already existing active rows.

START TRANSACTION;

INSERT INTO manual_service_bulletins (
    manual_id,
    sort_order,
    year_introduced,
    ac_mfg_service_bulletin_no,
    oem_service_bulletin_no,
    awd_no,
    identification_method,
    description,
    default_requirement,
    is_active,
    created_at,
    updated_at,
    deleted_at
)
SELECT
    manuals.id,
    sb.sort_order,
    sb.year_introduced,
    sb.ac_mfg_service_bulletin_no,
    sb.oem_service_bulletin_no,
    sb.awd_no,
    sb.identification_method,
    sb.description,
    sb.default_requirement,
    sb.is_active,
    NOW(),
    NOW(),
    NULL
FROM (
    SELECT '32-11-01RM' AS manual_number, 1 AS sort_order, '12/Apr/2006' AS year_introduced, '170-32-0011' AS ac_mfg_service_bulletin_no, '170-2801A-32-01' AS oem_service_bulletin_no, 'N/A' AS awd_no, '2801A/2802A0000-02 to -03.' AS identification_method, 'Replacement of MSE Seal' AS description, 'recommended' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 2 AS sort_order, '23/Apr/2015' AS year_introduced, '170-32-0021 R2' AS ac_mfg_service_bulletin_no, '170-2244A-32-01 R1' AS oem_service_bulletin_no, 'N/A' AS awd_no, '2244A0000-03/-04 to -05' AS identification_method, 'Shimmy Damper replacement' AS description, 'mandatory' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 3 AS sort_order, '23/Apr/2015' AS year_introduced, '170-32-0021 R2' AS ac_mfg_service_bulletin_no, '2801A32-07' AS oem_service_bulletin_no, 'N/A' AS awd_no, '2801A/2802A0000-02/03 to -04.' AS identification_method, 'MLG Shock Strut PN 2801A0000-03 and PN 2802A0000-03 - Reidentification of Shock Strut into PN 2801A0000-04 and 2802A0000-04' AS description, 'mandatory' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 4 AS sort_order, '27/Oct/2009' AS year_introduced, '170-32-0042' AS ac_mfg_service_bulletin_no, '2801A-32-02' AS oem_service_bulletin_no, 'N/A' AS awd_no, 'Log' AS identification_method, 'Downlock release Actuator attachment flange assembly bush replacement' AS description, 'recommended' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 5 AS sort_order, '28/Oct/2019' AS year_introduced, '170-32-0060 R1' AS ac_mfg_service_bulletin_no, '2801A-32-09 R2' AS oem_service_bulletin_no, 'N/A' AS awd_no, '2801A/2802A0000-05 to -06.' AS identification_method, 'UPR Bearing Support improvement Make sure this SB and 2801A-32-06 incorporated' AS description, 'mandatory' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 6 AS sort_order, '15/Jan/2019' AS year_introduced, '170-32-0049 R2' AS ac_mfg_service_bulletin_no, '2801A-32-05' AS oem_service_bulletin_no, 'N/A' AS awd_no, '2801A/2802A0000-04 to -05 Log' AS identification_method, 'Improvement of the Side Stay attachment Bolt' AS description, 'recommended' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 7 AS sort_order, '30/Sep/2010' AS year_introduced, '170-32-0046' AS ac_mfg_service_bulletin_no, '32-11-170-MLG-02' AS oem_service_bulletin_no, 'N/A' AS awd_no, 'Log' AS identification_method, 'Replacement of the Uplock Roller' AS description, 'mandatory' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 8 AS sort_order, '11/Jan/2013' AS year_introduced, '170-32-0047 R1' AS ac_mfg_service_bulletin_no, '32-11-170-MLG-01 R1' AS oem_service_bulletin_no, 'N/A' AS awd_no, 'Amdt. A' AS identification_method, 'Replacement of the Bolt P/N 1840-0029' AS description, 'recommended' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 9 AS sort_order, '27/Jan/2012' AS year_introduced, '170-32-0052 R1' AS ac_mfg_service_bulletin_no, '2801A-32-04' AS oem_service_bulletin_no, 'N/A' AS awd_no, 'Log' AS identification_method, 'Servicing Chart replacement' AS description, 'mandatory' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 10 AS sort_order, '28/Oct/2019' AS year_introduced, '170-32-0060 R1' AS ac_mfg_service_bulletin_no, '2801A-32-06 R2' AS oem_service_bulletin_no, 'N/A' AS awd_no, '2801A/2802A0000-05 to -06.' AS identification_method, 'Sliding Tube Assembly and UPR Bearing support improvements. Make sure this SB and 2801A-32-09 incorporated' AS description, 'mandatory' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 11 AS sort_order, '03/Jun/2014' AS year_introduced, '170-32-0071' AS ac_mfg_service_bulletin_no, '2801A-32-10' AS oem_service_bulletin_no, 'N/A' AS awd_no, 'Log' AS identification_method, 'Inspection and replacement of MLG AFT Pintle Pin P/N 1840-0024' AS description, 'recommended' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 12 AS sort_order, '15/Jun/2022' AS year_introduced, 'N/A' AS ac_mfg_service_bulletin_no, '2801A32-11 R3' AS oem_service_bulletin_no, 'N/A' AS awd_no, 'Log/Report to OEM' AS identification_method, 'Main Fitting wall thickness inspection.' AS description, 'recommended' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 13 AS sort_order, '19/Nov/2020' AS year_introduced, '170-32-0088 R1' AS ac_mfg_service_bulletin_no, '2801A-32-12' AS oem_service_bulletin_no, '11/7/2019' AS awd_no, 'Log' AS identification_method, 'AFT Pintle Pin P/N 1840-0024-Cross Bore repair' AS description, 'mandatory' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 14 AS sort_order, '28/Jun/2024' AS year_introduced, '170-32-0089 R2' AS ac_mfg_service_bulletin_no, '1840A-32-01 R1' AS oem_service_bulletin_no, 'N/A' AS awd_no, 'Log' AS identification_method, 'Replacement of MLG Bracket Assy P/N 1840A0700-03/-04 due to life limitation.' AS description, 'mandatory' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 15 AS sort_order, '05/Aug/2024' AS year_introduced, '170-32-A94 R2' AS ac_mfg_service_bulletin_no, 'N/A' AS oem_service_bulletin_no, 'E2024-05-09 R1' AS awd_no, 'Log' AS identification_method, 'INSPECTION/REPLACEMENT OF THE MAIN LANDING GEAR (MLG) LOCKING-STAY BRACKET ASSEMBLY NUT' AS description, 'mandatory' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 16 AS sort_order, '30/Oct/2025' AS year_introduced, '170-32-0095 R2' AS ac_mfg_service_bulletin_no, 'N/A' AS oem_service_bulletin_no, 'N/A' AS awd_no, 'Log' AS identification_method, 'MLG RETRACTION ACTUATOR BEARING AND BOLT - INSPECTION' AS description, 'recommended' AS default_requirement, 1 AS is_active
    UNION ALL
    SELECT '32-11-01RM' AS manual_number, 17 AS sort_order, '22/Nov/2024' AS year_introduced, '170-32-0096' AS ac_mfg_service_bulletin_no, 'N/A' AS oem_service_bulletin_no, 'N/A' AS awd_no, 'Log' AS identification_method, 'LOCKING-STAY BRACKET ASSEMBLY NUT - INSPECTION/REPLACEMENT. (not required if 170-32-0089 and 170-32-A94 incorp.)' AS description, 'recommended' AS default_requirement, 1 AS is_active
) AS sb
INNER JOIN manuals ON manuals.number = sb.manual_number
WHERE NOT EXISTS (
    SELECT 1
    FROM manual_service_bulletins existing
    WHERE existing.manual_id = manuals.id
      AND existing.sort_order = sb.sort_order
      AND COALESCE(existing.ac_mfg_service_bulletin_no, '') = COALESCE(sb.ac_mfg_service_bulletin_no, '')
      AND COALESCE(existing.oem_service_bulletin_no, '') = COALESCE(sb.oem_service_bulletin_no, '')
      AND existing.deleted_at IS NULL
);

SELECT ROW_COUNT() AS inserted_manual_service_bulletins;

COMMIT;

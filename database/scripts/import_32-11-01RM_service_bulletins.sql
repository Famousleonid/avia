START TRANSACTION;

SET @manual_id := (
    SELECT id
    FROM manuals
    WHERE number = '32-11-01RM'
    LIMIT 1
);

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
    @manual_id,
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
    SELECT 1 AS sort_order, '12/Apr/2006' AS year_introduced, '170-32-0011' AS ac_mfg_service_bulletin_no, '170-2801A-32-01' AS oem_service_bulletin_no, 'N/A' AS awd_no, '2801A/2802A0000-02 to -03.' AS identification_method, 'Replacement of MSE Seal' AS description, 'recommended' AS default_requirement, 1 AS is_active
    UNION ALL SELECT 2, '23/Apr/2015', '170-32-0021 R2', '170-2244A-32-01 R1', 'N/A', '2244A0000-03/-04 to -05', 'Shimmy Damper replacement', 'mandatory', 1
    UNION ALL SELECT 3, '23/Apr/2015', '170-32-0021 R2', '2801A32-07', 'N/A', '2801A/2802A0000-02/03 to -04.', 'MLG Shock Strut PN 2801A0000-03 and PN 2802A0000-03 - Reidentification of Shock Strut into PN 2801A0000-04 and 2802A0000-04', 'mandatory', 1
    UNION ALL SELECT 4, '27/Oct/2009', '170-32-0042', '2801A-32-02', 'N/A', 'Log', 'Downlock release Actuator attachment flange assembly bush replacement', 'recommended', 1
    UNION ALL SELECT 5, '28/Oct/2019', '170-32-0060 R1', '2801A-32-09 R2', 'N/A', '2801A/2802A0000-05 to -06.', 'UPR Bearing Support improvement Make sure this SB and 2801A-32-06 incorporated', 'mandatory', 1
    UNION ALL SELECT 6, '15/Jan/2019', '170-32-0049 R2', '2801A-32-05', 'N/A', '2801A/2802A0000-04 to -05 Log', 'Improvement of the Side Stay attachment Bolt', 'recommended', 1
    UNION ALL SELECT 7, '30/Sep/2010', '170-32-0046', '32-11-170-MLG-02', 'N/A', 'Log', 'Replacement of the Uplock Roller', 'mandatory', 1
    UNION ALL SELECT 8, '11/Jan/2013', '170-32-0047 R1', '32-11-170-MLG-01 R1', 'N/A', 'Amdt. A', 'Replacement of the Bolt P/N 1840-0029', 'recommended', 1
    UNION ALL SELECT 9, '27/Jan/2012', '170-32-0052 R1', '2801A-32-04', 'N/A', 'Log', 'Servicing Chart replacement', 'mandatory', 1
    UNION ALL SELECT 10, '28/Oct/2019', '170-32-0060 R1', '2801A-32-06 R2', 'N/A', '2801A/2802A0000-05 to -06.', 'Sliding Tube Assembly and UPR Bearing support improvements. Make sure this SB and 2801A-32-09 incorporated', 'mandatory', 1
    UNION ALL SELECT 11, '03/Jun/2014', '170-32-0071', '2801A-32-10', 'N/A', 'Log', 'Inspection and replacement of MLG AFT Pintle Pin P/N 1840-0024', 'recommended', 1
    UNION ALL SELECT 12, '15/Jun/2022', 'N/A', '2801A32-11 R3', 'N/A', 'Log/Report to OEM', 'Main Fitting wall thickness inspection.', 'recommended', 1
    UNION ALL SELECT 13, '19/Nov/2020', '170-32-0088 R1', '2801A-32-12', '11/7/2019', 'Log', 'AFT Pintle Pin P/N 1840-0024-Cross Bore repair', 'mandatory', 1
    UNION ALL SELECT 14, '28/Jun/2024', '170-32-0089 R2', '1840A-32-01 R1', 'N/A', 'Log', 'Replacement of MLG Bracket Assy P/N 1840A0700-03/-04 due to life limitation.', 'mandatory', 1
    UNION ALL SELECT 15, '05/Aug/2024', '170-32-A94 R2', 'N/A', 'E2024-05-09 R1', 'Log', 'INSPECTION/REPLACEMENT OF THE MAIN LANDING GEAR (MLG) LOCKING-STAY BRACKET ASSEMBLY NUT', 'mandatory', 1
    UNION ALL SELECT 16, '30/Oct/2025', '170-32-0095 R2', 'N/A', 'N/A', 'Log', 'MLG RETRACTION ACTUATOR BEARING AND BOLT - INSPECTION', 'recommended', 1
    UNION ALL SELECT 17, '22/Nov/2024', '170-32-0096', 'N/A', 'N/A', 'Log', 'LOCKING-STAY BRACKET ASSEMBLY NUT - INSPECTION/REPLACEMENT. (not required if 170-32-0089 and 170-32-A94 incorp.)', 'recommended', 1
) AS sb
WHERE @manual_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM manual_service_bulletins existing
      WHERE existing.manual_id = @manual_id
        AND existing.sort_order = sb.sort_order
        AND COALESCE(existing.ac_mfg_service_bulletin_no, '') = COALESCE(sb.ac_mfg_service_bulletin_no, '')
        AND COALESCE(existing.oem_service_bulletin_no, '') = COALESCE(sb.oem_service_bulletin_no, '')
        AND existing.deleted_at IS NULL
  );

SELECT @manual_id AS target_manual_id, ROW_COUNT() AS inserted_service_bulletins;

COMMIT;

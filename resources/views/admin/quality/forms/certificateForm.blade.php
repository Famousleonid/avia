@php
    $manual = $current_wo->unit?->manual;
    $defaultStatusWork = trim((string) ($current_wo->instruction?->name ?? '')) ?: 'Overhaul';
    $selectedCertificateInstructionId = $selectedCertificateInstructionId ?? ($current_wo->instruction_id ? (int) $current_wo->instruction_id : null);
    $certificateStatusOptions = collect($certificateStatusOptions ?? [])
        ->filter(fn ($option): bool => trim((string) ($option->name ?? $option['name'] ?? '')) !== '')
        ->values();
    $formatStatusWork = static function (string $value): string {
        return match (trim($value)) {
            'Test & inspect' => 'Tested/Inspected',
            'Repair' => 'Repaired',
            'Overhaul' => 'Overhauled',
            default => trim($value),
        };
    };
    $selectedStatusOption = $certificateStatusOptions->first(
        fn ($option): bool => (int) ($option->id ?? $option['id'] ?? 0) === (int) $selectedCertificateInstructionId
    );
    $selectedStatusWork = trim((string) ($selectedStatusOption->name ?? $selectedStatusOption['name'] ?? $defaultStatusWork));
    $status = $formatStatusWork($selectedStatusWork);
    $manualRevisionDateSource = $manual?->revision_date;
    $manualRevisionNumber = trim((string) ($manual?->revision_number ?? ''));
    $formatCertificateManualNumber = static function ($number): string {
        $number = trim((string) $number);

        if (preg_match('/^(\d{2}-\d{2}-\d{2})\s+\d{2}$/', $number, $matches)) {
            return $matches[1];
        }

        return $number;
    };
    $certificateManualNumber = $formatCertificateManualNumber($manual?->number ?? '');
    $formatCertificateDate = static function ($date): string {
        $formatted = format_project_date($date);
        if (! $formatted) {
            return '';
        }

        return preg_replace_callback(
            '/\/([a-z]{3})\//i',
            static fn (array $match): string => '/' . ucfirst(strtolower((string) $match[1])) . '/',
            $formatted
        );
    };
    $formatDateInputValue = static function ($date): string {
        if ($date === null || trim((string) $date) === '') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    };
    $manualDate = $formatCertificateDate($manualRevisionDateSource);
    $manualRevisionDateInputValue = $manualDate;
    $manualRevisionDateIso = $formatDateInputValue($manualRevisionDateSource);
    $manualRevisionNumberInputValue = $manualRevisionNumber;
    $defaultCertificateDateSource = $current_wo->doneDate() ?? $current_wo->approve_at ?? now();
    $defaultCertificateDateDisplay = $formatCertificateDate($defaultCertificateDateSource);
    $defaultCertificateDateInputValue = $formatDateInputValue($defaultCertificateDateSource);
    $certificateDateIso = trim((string) ($certificateDateIso ?? ''));
    $certificateDateSource = $certificateDateIso !== '' ? $certificateDateIso : $defaultCertificateDateSource;
    $completedDate = $formatCertificateDate($certificateDateSource);
    $certificateDateInputValue = $formatDateInputValue($certificateDateSource);
    $overhauledOnDateInputValue = $formatDateInputValue($overhauledOnDate ?? '');
    $overhauledOnDateDisplay = $overhauledOnDateInputValue !== ''
        ? $formatCertificateDate($overhauledOnDateInputValue)
        : '';
    $customerPo = trim((string) ($current_wo->customer_po ?? ''));
    $managerOptions = collect($managerOptions ?? []);
    $canEditCertificateManager = (bool) ($canEditCertificateManager ?? false);
    $selectedCertificateManagerId = $selectedCertificateManagerId ?? null;
    $certificateManagerName = trim((string) ($certificateManagerName ?? auth()->user()?->selection_name ?? ''));
    $includeLandingGearLogCard = (bool) ($includeLandingGearLogCard ?? true);
    $includeRoycoService = (bool) ($includeRoycoService ?? false);
    $includeOverhauledOn = (bool) ($includeOverhauledOn ?? false);
    $hasOrderedReplacementParts = (bool) ($hasOrderedReplacementParts ?? false);
    $certificateDetailOpen = (bool) ($certificateDetailOpen ?? false);
    $certificateItemSettings = collect($certificateItemSettings ?? [])
        ->map(fn ($settings): array => is_array($settings) ? $settings : [])
        ->all();
    $certificateLogComponents = collect($certificateLogComponents ?? []);
    $selectedCertificateTrackingMode = trim((string) ($selectedCertificateTrackingMode ?? ''));
    $mainItemDescription = trim((string) ($current_wo->unit?->name ?? ''));
    $mainItemPartNumber = trim((string) ($current_wo->unit?->part_number ?? ''));
    $modifiedPartNumber = trim((string) ($current_wo->modified ?? ''));
    $firstNonBlank = function (...$values): string {
        foreach ($values as $value) {
            $text = trim((string) $value);
            if ($text !== '' && $text !== '-') {
                return $text;
            }
        }

        return '';
    };
    $serialNumber = $firstNonBlank($current_wo->serial_number);
    $trackingNumber = trim('W' . (string) $current_wo->number);
    $mainPartNumberLines = [$modifiedPartNumber !== '' ? $modifiedPartNumber : $mainItemPartNumber];
    $mainSerialNumberLines = [$serialNumber];
    $decodeLogRows = static function (mixed $value): array {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($row, $key) => is_int($key) && is_array($row))
            ->values()
            ->all();
    };
    $logRows = $decodeLogRows($certificateLogCard?->component_data_out ?: $certificateLogCard?->component_data);
    $collapseDetailText = static fn ($value): string => trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
    $formatDetailOptionLabel = static function (...$values) use ($collapseDetailText): string {
        return collect($values)
            ->map(fn ($value): string => $collapseDetailText($value))
            ->filter()
            ->implode(' | ');
    };
    $logRowLifeRemarkText = static function (array $row, bool $includeAircraftRecords = false) use ($firstNonBlank): string {
        $aircraftRows = $includeAircraftRecords
            ? collect($row['qa_aircraft_records'] ?? [])
            : collect();
        $aircraftLifeRow = $aircraftRows->first(function ($aircraftRow) {
            return is_array($aircraftRow) && (
                trim((string) ($aircraftRow['fit_csn'] ?? '')) !== ''
                || trim((string) ($aircraftRow['removed_csn'] ?? '')) !== ''
                || trim((string) ($aircraftRow['fit_cso'] ?? '')) !== ''
                || trim((string) ($aircraftRow['removed_cso'] ?? '')) !== ''
            );
        }) ?: [];
        $lifeCsn = $firstNonBlank(
            $row['qa_fit_csn'] ?? null,
            $row['fit_csn'] ?? null,
            $row['qa_removed_csn'] ?? null,
            $row['removed_csn'] ?? null,
            $aircraftLifeRow['fit_csn'] ?? null,
            $aircraftLifeRow['removed_csn'] ?? null,
        );
        $lifeCso = $firstNonBlank(
            $row['qa_fit_cso'] ?? null,
            $row['fit_cso'] ?? null,
            $row['qa_removed_cso'] ?? null,
            $row['removed_cso'] ?? null,
            $aircraftLifeRow['fit_cso'] ?? null,
            $aircraftLifeRow['removed_cso'] ?? null,
        );
        $lifeRemark = collect([
            $lifeCsn !== '' ? 'CSN-' . $lifeCsn : null,
            $lifeCso !== '' ? 'CSO-' . $lifeCso : null,
        ])->filter()->implode('; ');

        return $lifeRemark !== '' ? $lifeRemark . '.' : '';
    };
    $lifeRemarkFallbackText = 'CSN: N/A';
    $defaultLifeRemarkText = $logRowLifeRemarkText($logRows[0] ?? [], true) ?: $lifeRemarkFallbackText;
    $certificateDetailOptions = collect([
        [
            'key' => 'main',
            'label' => $formatDetailOptionLabel(
                $mainItemDescription,
                collect($mainPartNumberLines)->filter()->implode(' / '),
                collect($mainSerialNumberLines)->filter()->implode(' / ')
            ),
            'tracking_number' => $trackingNumber,
            'source' => 'main',
            'component_id' => null,
            'description' => $mainItemDescription,
            'part_number' => collect($mainPartNumberLines)->filter()->implode("\n"),
            'part_number_secondary' => '',
            'serial_number' => collect($mainSerialNumberLines)->filter()->implode("\n"),
            'serial_number_secondary' => '',
            'life_remark_text' => $defaultLifeRemarkText,
        ],
    ]);
    $logDetailOptions = collect($logRows)
        ->map(function (array $row, int $index) use ($firstNonBlank, $formatDetailOptionLabel, $certificateLogComponents, $logRowLifeRemarkText, $lifeRemarkFallbackText): array {
            $component = $certificateLogComponents->get((int) ($row['component_id'] ?? 0));
            $description = $firstNonBlank(
                $row['qa_description'] ?? null,
                $row['name'] ?? null,
                $row['description'] ?? null,
                $component?->name,
            );
            $partNumber = $firstNonBlank($row['qa_part_number'] ?? null, $row['part_number'] ?? null, $component?->part_number);
            $assyPartNumber = $firstNonBlank($row['assy_part_number'] ?? null, $component?->assy_part_number);
            $serialNumber = $firstNonBlank($row['qa_serial_number'] ?? null, $row['serial_number'] ?? null);
            $assySerialNumber = $firstNonBlank($row['assy_serial_number'] ?? null);
            $primaryPartNumber = $assyPartNumber !== '' ? $assyPartNumber : $partNumber;
            $secondaryPartNumber = $assyPartNumber !== '' && strcasecmp($assyPartNumber, $partNumber) !== 0
                ? $partNumber
                : '';
            $primarySerialNumber = $assySerialNumber !== '' ? $assySerialNumber : $serialNumber;
            $secondarySerialNumber = $assySerialNumber !== '' && strcasecmp($assySerialNumber, $serialNumber) !== 0
                ? $serialNumber
                : '';

            return [
                'key' => 'log:' . $index,
                'label' => $formatDetailOptionLabel(
                    $description,
                    collect([$primaryPartNumber, $secondaryPartNumber])->filter()->implode(' / '),
                    collect([$primarySerialNumber, $secondarySerialNumber])->filter()->implode(' / ')
                ),
                'source' => 'log',
                'component_id' => (int) ($row['component_id'] ?? 0),
                'description' => $description,
                'part_number' => $primaryPartNumber,
                'part_number_secondary' => $secondaryPartNumber,
                'serial_number' => $primarySerialNumber,
                'serial_number_secondary' => $secondarySerialNumber,
                'life_remark_text' => $logRowLifeRemarkText($row) ?: $lifeRemarkFallbackText,
            ];
        })
        ->filter(fn (array $option): bool => $option['description'] !== '' || $option['part_number'] !== '' || $option['serial_number'] !== '')
        ->values()
        ->map(fn (array $option, int $selectIndex): array => $option + [
            'tracking_number' => $trackingNumber . '-' . ($selectIndex + 1),
        ]);
    $certificateDetailOptions = $certificateDetailOptions->concat($logDetailOptions)->values();
    $selectedCertificateItemSource = trim((string) ($selectedCertificateItemSource ?? 'main')) ?: 'main';
    $selectedCertificateItem = $certificateDetailOptions->firstWhere('key', $selectedCertificateItemSource)
        ?? $certificateDetailOptions->firstWhere('key', 'main');
    $selectedCertificateItemSource = (string) ($selectedCertificateItem['key'] ?? 'main');
    $selectedCertificateTrackingMode = $selectedCertificateItemSource === 'main' && strcasecmp($selectedCertificateTrackingMode, 'c') === 0
        ? 'c'
        : '';
    $selectedCertificateStateKey = $selectedCertificateItemSource === 'main' && $selectedCertificateTrackingMode === 'c'
        ? 'main:c'
        : $selectedCertificateItemSource;
    $trackingNumber = trim((string) ($selectedCertificateItem['tracking_number'] ?? $trackingNumber));
    if ($selectedCertificateTrackingMode === 'c') {
        $trackingNumber .= '-C';
    }
    $itemDescription = trim((string) ($selectedCertificateItem['description'] ?? $mainItemDescription));
    $partNumber = trim((string) ($selectedCertificateItem['part_number'] ?? ''));
    $partNumberSecondary = trim((string) ($selectedCertificateItem['part_number_secondary'] ?? ''));
    $serialNumber = trim((string) ($selectedCertificateItem['serial_number'] ?? ''));
    $serialNumberSecondary = trim((string) ($selectedCertificateItem['serial_number_secondary'] ?? ''));
    $selectedCertificateItemSettings = $certificateItemSettings[$selectedCertificateStateKey] ?? [];
    $certificateItemStringSetting = static function (string $key, string $default = '') use ($selectedCertificateItemSettings): string {
        if (array_key_exists($key, $selectedCertificateItemSettings)) {
            return trim((string) $selectedCertificateItemSettings[$key]);
        }

        return $default;
    };
    $certificateRemarkChecked = static function (string $key, bool $default) use ($selectedCertificateItemSettings): bool {
        if (array_key_exists($key, $selectedCertificateItemSettings)) {
            return filter_var($selectedCertificateItemSettings[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
        }

        return $default;
    };
    $lifeRemarkText = trim((string) ($selectedCertificateItem['life_remark_text'] ?? $defaultLifeRemarkText));
    $workOrderText = $customerPo !== '' ? $customerPo : '-';
    if ($selectedCertificateStateKey === 'main:c') {
        $workOrderText = $certificateItemStringSetting('certificate_work_order', $workOrderText);
        $itemDescription = $certificateItemStringSetting('certificate_item_description', $itemDescription);
        $partNumberOverride = $certificateItemStringSetting('certificate_item_part');
        $serialNumberOverride = $certificateItemStringSetting('certificate_item_serial');
        if ($partNumberOverride !== '') {
            $legacyPartNumberLines = preg_split('/\r\n|\r|\n/', $partNumberOverride) ?: [];
            $partNumber = trim((string) array_shift($legacyPartNumberLines));
            if ($partNumberSecondary === '' && $legacyPartNumberLines !== []) {
                $partNumberSecondary = trim(implode(' ', $legacyPartNumberLines));
            }
        }
        if ($serialNumberOverride !== '') {
            $legacySerialNumberLines = preg_split('/\r\n|\r|\n/', $serialNumberOverride) ?: [];
            $serialNumber = trim((string) array_shift($legacySerialNumberLines));
            if ($serialNumberSecondary === '' && $legacySerialNumberLines !== []) {
                $serialNumberSecondary = trim(implode(' ', $legacySerialNumberLines));
            }
        }
        $statusOverride = $certificateItemStringSetting('certificate_status_work');
        if ($statusOverride !== '') {
            $status = $statusOverride;
        }
        $statusInstructionOverride = $certificateItemStringSetting('certificate_status_instruction_id');
        if ($statusInstructionOverride !== '') {
            $selectedCertificateInstructionId = (int) $statusInstructionOverride;
        }
    }
    $partNumberSecondary = $certificateItemStringSetting('certificate_item_part_secondary', $partNumberSecondary);
    $serialNumberSecondary = $certificateItemStringSetting('certificate_item_serial_secondary', $serialNumberSecondary);
    $includePartNumberSecondary = $certificateRemarkChecked('include_certificate_item_part_secondary', $partNumberSecondary !== '');
    $includeSerialNumberSecondary = $certificateRemarkChecked('include_certificate_item_serial_secondary', $serialNumberSecondary !== '');
    $normalizeAdNumber = static function ($value): string {
        $text = trim((string) $value);
        if ($text === '' || strcasecmp($text, 'N/A') === 0) {
            return '';
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $text, $match)) {
            return sprintf('%04d-%02d-%02d', (int) $match[3], (int) $match[1], (int) $match[2]);
        }

        return $text;
    };
    $incorporatedStatuses = [
        \App\Models\WorkorderServiceBulletinLog::STATUS_PREVIOUSLY_CARRIED_OUT,
        \App\Models\WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT,
    ];
    $incorporatedBulletinLogs = collect($current_wo->serviceBulletinLogs ?? [])
        ->filter(fn ($log): bool => in_array((string) ($log->status ?? ''), $incorporatedStatuses, true))
        ->filter(fn ($log): bool => (bool) $log->serviceBulletin);
    $airworthinessDirectives = $incorporatedBulletinLogs
        ->map(fn ($log): string => $normalizeAdNumber($log->serviceBulletin?->awd_no ?? ''))
        ->filter()
        ->unique()
        ->values();
    $serviceBulletins = $incorporatedBulletinLogs
        ->map(function ($log): string {
            $bulletin = $log->serviceBulletin;
            $number = trim((string) ($bulletin?->ac_mfg_service_bulletin_no ?? ''));
            if ($number === '' || strcasecmp($number, 'N/A') === 0) {
                $number = trim((string) ($bulletin?->oem_service_bulletin_no ?? ''));
            }

            return strcasecmp($number, 'N/A') === 0 ? '' : $number;
        })
        ->filter()
        ->unique()
        ->values();
    $airworthinessText = 'Airworthiness Directives '
        . ($airworthinessDirectives->isNotEmpty() ? $airworthinessDirectives->implode(', ') : 'none')
        . ' and Service Bulletins: '
        . ($serviceBulletins->isNotEmpty() ? $serviceBulletins->implode(', ') : 'none')
        . ' incorporated.';
    if ($selectedCertificateStateKey === 'main:c' && array_key_exists('certificate_airworthiness_remark', $selectedCertificateItemSettings)) {
        $savedAirworthinessRemark = $selectedCertificateItemSettings['certificate_airworthiness_remark'];
        $airworthinessText = is_scalar($savedAirworthinessRemark)
            ? trim((string) $savedAirworthinessRemark)
            : '';
    }
    $landingGearLogCardText = 'Landing Gear Log Card attached';
    if ($selectedCertificateStateKey === 'main:c' && array_key_exists('certificate_landing_gear_log_card_remark', $selectedCertificateItemSettings)) {
        $savedLandingGearLogCardRemark = $selectedCertificateItemSettings['certificate_landing_gear_log_card_remark'];
        $landingGearLogCardText = is_scalar($savedLandingGearLogCardRemark)
            ? trim((string) $savedLandingGearLogCardRemark)
            : '';
    }
    $roycoServiceText = 'Serviced with ROYCO LGF (Yellow)';
    if ($selectedCertificateStateKey === 'main:c' && array_key_exists('certificate_royco_service_remark', $selectedCertificateItemSettings)) {
        $savedRoycoServiceRemark = $selectedCertificateItemSettings['certificate_royco_service_remark'];
        $roycoServiceText = is_scalar($savedRoycoServiceRemark)
            ? trim((string) $savedRoycoServiceRemark)
            : '';
    }
    $revisionText = '';
    $revisionControlPrefix = '';
    $hasRevisionControls = $manual !== null && ($manualDate !== '' || $manualRevisionNumber !== '');
    if ($hasRevisionControls) {
        $revisionControlPrefix = ', Rev # ';
        $revisionText = $revisionControlPrefix . $manualRevisionNumber . ' dated ' . $manualDate;
    }
    $certificateCmmExtraText = trim((string) ($selectedCertificateItemSettings['certificate_cmm_extra_text'] ?? ''));
    $certificateCmmExtraSuffix = $certificateCmmExtraText !== '' ? ', ' . $certificateCmmExtraText : '';
    $statusRemarkBaseSuffix = ' in accordance with CMM # ' . $certificateManualNumber;
    $statusRemarkSuffix = $statusRemarkBaseSuffix . $revisionText . $certificateCmmExtraSuffix . '.';
    $replacementPartsRemarkOptions = [
        'tdr' => 'For the replacement parts refer to Teardown Report.',
        'none' => 'Replacement parts: None.',
    ];
    $defaultReplacementPartsRemark = $hasOrderedReplacementParts ? 'tdr' : 'none';
    $selectedReplacementPartsRemark = strtolower(trim((string) ($selectedCertificateItemSettings['certificate_replacement_parts_remark'] ?? '')));
    if (! array_key_exists($selectedReplacementPartsRemark, $replacementPartsRemarkOptions)) {
        $selectedReplacementPartsRemark = $defaultReplacementPartsRemark;
    }
    $lifeRemarkText = $certificateItemStringSetting('certificate_life_remark', $lifeRemarkText);
    $remarks = [
        [
            'text' => trim($status . $statusRemarkSuffix),
            'status_remark_suffix' => $statusRemarkSuffix,
            'status_remark_base_suffix' => $statusRemarkBaseSuffix,
            'status_remark_cmm_extra_text' => $certificateCmmExtraText,
            'status_remark_revision_prefix' => $hasRevisionControls
                ? $revisionControlPrefix
                : '',
        ],
        ['text' => 'Full details of work performed in work order W' . $current_wo->number . '.'],
        [
            'text' => $replacementPartsRemarkOptions[$selectedReplacementPartsRemark],
            'replacement_parts_remark' => true,
            'replacement_parts_options' => $replacementPartsRemarkOptions,
            'replacement_parts_selected' => $selectedReplacementPartsRemark,
            'replacement_parts_default' => $defaultReplacementPartsRemark,
        ],
        [
            'text' => $airworthinessText,
            'airworthiness_remark' => true,
            'setting_key' => 'include_airworthiness_remark',
            'checked' => $certificateRemarkChecked('include_airworthiness_remark', false),
        ],
        [
            'text' => $landingGearLogCardText,
            'setting_key' => 'include_landing_gear_log_card',
            'checked' => $certificateRemarkChecked('include_landing_gear_log_card', $includeLandingGearLogCard),
            'separate_remark_key' => 'certificate_landing_gear_log_card_remark',
        ],
        ['text' => $lifeRemarkText, 'life_remark' => true],
        [
            'text' => 'Overhauled on ' . ($overhauledOnDateDisplay !== '' ? $overhauledOnDateDisplay : '...................'),
            'setting_key' => 'include_overhauled_on',
            'checked' => $certificateRemarkChecked('include_overhauled_on', $includeOverhauledOn),
            'overhauled_on_remark' => true,
        ],
        [
            'text' => $roycoServiceText,
            'setting_key' => 'include_royco_service',
            'checked' => $certificateRemarkChecked('include_royco_service', $includeRoycoService),
            'separate_remark_key' => 'certificate_royco_service_remark',
        ],
    ];
    $defaultCorrectionRemark = 'This certificate issued to correct CSN info in Block 12 on original certificate W'
            . $current_wo->number
            . ' dated '
            . $completedDate
            . ', and serves as a historical record and not as a statement of current condition. '
            . 'This certificate should be retained with original certificate according to the retention period applicable to the original certificate.';
    $correctionRemarkText = $defaultCorrectionRemark;
    if (array_key_exists('certificate_c_correction_remark', $selectedCertificateItemSettings)) {
        $savedCorrectionRemark = $selectedCertificateItemSettings['certificate_c_correction_remark'];
        $correctionRemarkText = is_scalar($savedCorrectionRemark)
            ? trim((string) $savedCorrectionRemark)
            : '';
    }
    $remarks[] = [
        'text' => $correctionRemarkText,
        'c_correction_remark' => true,
    ];
    $certificateFreeRemarks = collect([1, 2])->mapWithKeys(function (int $index) use ($certificateItemStringSetting, $certificateRemarkChecked): array {
        $valueKey = 'certificate_free_remark_' . $index;
        $includeKey = 'include_certificate_free_remark_' . $index;
        $text = $certificateItemStringSetting($valueKey);

        return [$index => [
            'value_key' => $valueKey,
            'include_key' => $includeKey,
            'text' => $text,
            'checked' => $certificateRemarkChecked($includeKey, false),
        ]];
    });
    if ($selectedCertificateStateKey === 'main:c' && is_array($selectedCertificateItemSettings['certificate_remarks'] ?? null)) {
        $savedCertificateRemarks = array_values($selectedCertificateItemSettings['certificate_remarks']);
        foreach ($remarks as $remarkIndex => $remark) {
            $isSourceControlledRemark = array_key_exists('status_remark_suffix', $remark)
                || ! empty($remark['life_remark'])
                || ! empty($remark['replacement_parts_remark'])
                || ! empty($remark['airworthiness_remark'])
                || ! empty($remark['separate_remark_key'])
                || ! empty($remark['overhauled_on_remark'])
                || ! empty($remark['c_correction_remark']);
            if (! $isSourceControlledRemark && array_key_exists($remarkIndex, $savedCertificateRemarks)) {
                $remarks[$remarkIndex]['text'] = trim((string) $savedCertificateRemarks[$remarkIndex]);
            }
        }
        foreach (array_slice($savedCertificateRemarks, count($remarks)) as $extraRemark) {
            $remarks[] = [
                'text' => trim((string) $extraRemark),
                'user_c_remark' => true,
            ];
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('CERTIFICATE') }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 9.35mm 6.35mm 6.35mm 6.35mm;
        }

        * {
            box-sizing: border-box;
        }

        :root {
            --arc-label-size: 10px;
            --arc-title-size: 18px;
            --arc-value-size: 14px;
            --arc-org-size: 12px;
            --arc-remarks-size: 14px;
            --arc-remarks-line-height: 1.22;
            --arc-installer-title-size: 12px;
            --arc-installer-size: 10px;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            background: #f2f2f2;
            color: #000;
            font-family: "Times New Roman", Times, serif;
        }

        body {
            padding: calc(0.28in + 3mm) 0.32in 0.28in;
        }

        .arc-toolbar {
            width: 10.36in;
            margin: 0 auto 0.12in;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        .arc-print-button {
            border: 1px solid #0d6efd;
            border-radius: 4px;
            background: #0d6efd;
            color: #fff;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            padding: 8px 14px;
            cursor: pointer;
        }

        .arc-print-button:hover,
        .arc-print-button:focus {
            background: #0b5ed7;
            border-color: #0a58ca;
        }

        .arc-sheet {
            width: 10.36in;
            height: 7.74in;
            min-height: 0;
            margin: 0;
            border: 2px solid #000;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .arc-sheet > .arc-top,
        .arc-sheet > .arc-org,
        .arc-sheet > .arc-items,
        .arc-sheet > .arc-remarks,
        .arc-sheet > .arc-cert-row,
        .arc-sheet > .arc-previous {
            flex: 0 0 auto;
            min-height: 0;
            overflow: hidden;
        }

        .arc-form-shell {
            position: relative;
            width: 10.36in;
            margin: 0 auto;
        }

        .arc-detail-picker {
            position: absolute;
            top: 0;
            left: calc(100% + 0.04in);
            width: 2.55in;
            height: 7.74in;
            display: flex;
            flex: 0 0 auto;
            flex-direction: column;
            gap: 6px;
            font-family: Arial, sans-serif;
        }

        .arc-detail-picker-header {
            display: flex;
            align-items: stretch;
            gap: 6px;
        }

        .arc-detail-main {
            flex: 1 1 auto;
            min-width: 0;
            border: 1px solid #333;
            border-radius: 4px;
            background: #fff;
            color: #000;
            font-size: 12px;
            line-height: 1.2;
            padding: 6px;
            text-align: left;
            cursor: pointer;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .arc-detail-main.is-active {
            border-color: #0d6efd;
            box-shadow: inset 0 0 0 1px #0d6efd;
        }

        .arc-detail-toggle {
            flex: 0 0 auto;
            border: 1px solid #333;
            border-radius: 4px;
            background: #fff;
            color: #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 0 6px;
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1;
            cursor: pointer;
            user-select: none;
        }

        .arc-detail-toggle input {
            width: 14px;
            height: 14px;
            margin: 0;
        }

        .arc-detail-select {
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
            border: 1px solid #333;
            border-radius: 4px;
            background: #fff;
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.25;
            padding: 4px;
        }

        .arc-detail-select[hidden] {
            display: none;
        }

        .arc-item-multiline {
            white-space: pre-line;
        }

        .arc-description-cell {
            padding: 0 4px;
        }

        .arc-item-description-input {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: calc(0.56in - 4px);
            border: 0;
            background: transparent;
            color: #000;
            font-family: "Times New Roman", Times, serif;
            font-size: inherit;
            font-weight: inherit;
            line-height: 1.15;
            text-align: center;
            outline: 0;
            padding: 0 2px;
            white-space: pre-line;
            cursor: text;
            transition: background-color 120ms ease;
        }

        .arc-item-description-input:focus {
            background: #eeeeee;
        }

        .arc-c-editable {
            min-width: 0;
            outline: 0;
            white-space: pre-line;
        }

        .arc-c-editable[contenteditable="true"] {
            cursor: text;
        }

        .arc-c-editable[contenteditable="true"]:focus {
            background: #eeeeee;
        }

        .arc-c-editable.is-saving {
            opacity: 0.65;
        }

        .arc-c-editable.is-invalid {
            box-shadow: inset 0 -1px 0 #dc3545;
        }

        .arc-item-description-input.is-saving {
            opacity: 0.65;
        }

        .arc-item-description-input.is-invalid {
            box-shadow: inset 0 -1px 0 #dc3545;
        }

        .arc-row {
            display: grid;
            border-bottom: 1.5px solid #000;
        }

        .arc-cell {
            position: relative;
            min-width: 0;
            border-right: 1.5px solid #000;
            padding: 2px 4px;
            overflow: hidden;
        }

        .arc-cell:last-child {
            border-right: 0;
        }

        .arc-label {
            font-size: var(--arc-label-size);
            line-height: 1.02;
        }

        .arc-value {
            font-size: var(--arc-value-size);
            line-height: 1.08;
            white-space: pre-line;
        }

        .arc-top {
            grid-template-columns: 27.1% 49% 23.9%;
            height: calc(0.78in - 2mm);
            min-height: 0;
        }

        .arc-top .arc-label {
            white-space: nowrap;
        }

        .arc-country {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 0.1in;
        }

        .arc-country .arc-label {
            position: absolute;
            top: 3px;
            left: 5px;
            width: auto;
            font-size: var(--arc-label-size);
        }

        .arc-country-name {
            margin-top: 0.08in;
            font-size: var(--arc-title-size);
            font-weight: 700;
        }

        .arc-title {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 0.18in;
        }

        .arc-title .arc-label {
            position: absolute;
            top: 3px;
            left: 5px;
            align-self: flex-start;
        }

        .arc-title-main {
            margin-top: 0.03in;
            font-size: var(--arc-title-size);
            line-height: 1.05;
            font-weight: 700;
            letter-spacing: 0;
            text-align: center;
            white-space: nowrap;
        }

        .arc-title-sub {
            font-size: var(--arc-title-size);
            font-weight: 700;
            text-align: center;
        }

        .arc-tracking {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.12in;
        }

        .arc-tracking .arc-label {
            position: absolute;
            top: 3px;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            text-align: center;
        }

        .arc-tracking-no {
            margin-top: 0.12in;
            font-size: var(--arc-value-size);
            letter-spacing: 0;
        }

        .arc-handline {
            display: none;
        }

        .arc-org {
            grid-template-columns: 76.1% 23.9%;
            height: 0.88in;
            min-height: 0;
        }

        .arc-org-block {
            display: grid;
            grid-template-columns: 27.5% 45.5% 27%;
            align-items: center;
            padding: 0.18in 0.12in 0.08in 0.08in;
        }

        .arc-org .arc-label,
        .arc-work-order .arc-label {
            position: absolute;
            top: 3px;
            left: 5px;
        }

        .arc-work-order .arc-label {
            left: 50%;
            width: 100%;
            text-align: center;
            transform: translateX(-50%);
        }

        .arc-top .arc-label,
        .arc-org .arc-label,
        .arc-work-order .arc-label {
            font-size: calc(var(--arc-label-size) + 2px);
        }

        .arc-country-name,
        .arc-title-main,
        .arc-title-sub {
            font-size: calc(var(--arc-title-size) + 2px);
        }

        .arc-tracking-no {
            font-size: calc(var(--arc-value-size) + 2px);
        }

        .arc-logo {
            display: block;
            width: 1.52in;
            max-width: calc(100% - 0.08in);
            max-height: 0.55in;
            justify-self: start;
            margin: 0;
            object-fit: contain;
            filter: grayscale(1) contrast(1.8);
        }

        .arc-address {
            font-size: calc(var(--arc-org-size) + 2px);
            font-weight: 700;
            line-height: 1.2;
        }

        .arc-contact {
            align-self: start;
            margin-top: 2px;
            font-size: calc(var(--arc-org-size) + 2px);
            font-weight: 700;
            line-height: 1.18;
        }

        .arc-url {
            display: inline-block;
            font-weight: 400;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .arc-work-order {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .arc-work-order .arc-value {
            margin-top: 0.12in;
            font-size: calc(var(--arc-value-size) + 2px);
            letter-spacing: 0;
        }

        .arc-items {
            width: 100%;
            height: calc(0.22in + 0.56in);
            border-collapse: collapse;
            table-layout: fixed;
        }

        .arc-items th,
        .arc-items td {
            border-right: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            text-align: center;
            vertical-align: middle;
            padding: 2px 4px;
        }

        .arc-items th:last-child,
        .arc-items td:last-child {
            border-right: 0;
        }

        .arc-items th {
            height: 0.22in;
            font-size: calc(var(--arc-label-size) + 2px);
            font-weight: 400;
        }

        .arc-items td {
            height: 0.56in;
            max-height: 0.56in;
            font-size: calc(var(--arc-value-size) + 2px);
            line-height: 1.15;
            overflow: hidden;
        }

        .arc-item-identifier-cell {
            padding: 2px 4px;
        }

        .arc-item-identifier-values {
            width: 100%;
            height: calc(0.56in - 4px);
            min-height: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            overflow: hidden;
        }

        .arc-item-identifier-primary {
            width: 100%;
            min-width: 0;
            flex: 0 0 auto;
            overflow: hidden;
            text-align: center;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .arc-item-secondary-line {
            width: 100%;
            min-width: 0;
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
        }

        .arc-item-secondary-input {
            width: calc(100% - 18px);
            min-width: 0;
            height: 20px;
            border: 0;
            border-bottom: 1px dotted #777;
            border-radius: 0;
            padding: 0 2px;
            background: transparent;
            color: #000;
            font-family: "Times New Roman", Times, serif;
            font-size: calc(var(--arc-value-size) + 1px);
            line-height: 1;
            text-align: center;
            outline: 0;
        }

        .arc-item-secondary-input:focus {
            background: #eeeeee;
        }

        .arc-item-secondary-input.is-saving {
            opacity: 0.65;
        }

        .arc-item-secondary-input.is-invalid {
            border-bottom-color: #dc3545;
        }

        .arc-item-secondary-toggle {
            display: inline-flex;
            flex: 0 0 15px;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .arc-item-secondary-toggle input {
            width: 13px;
            height: 13px;
            margin: 0;
        }

        .arc-item-secondary-print-value {
            display: none;
        }

        .arc-status-work-cell {
            position: relative;
        }

        .arc-status-work-select {
            width: calc(100% - 0.08in);
            border: 0;
            background: transparent;
            color: #000;
            font-family: "Times New Roman", Times, serif;
            font-size: calc(var(--arc-value-size) + 2px);
            font-weight: 400;
            line-height: 1.15;
            text-align: center;
            text-align-last: center;
            outline: 0;
        }

        .arc-status-work-print-value {
            display: none;
        }

        .arc-item-no {
            width: 5.9%;
        }

        .arc-description {
            width: 27.7%;
        }

        .arc-part {
            width: 21.4%;
        }

        .arc-qty {
            width: 6.85%;
        }

        .arc-serial {
            width: 19.45%;
        }

        .arc-status {
            width: 18.7%;
        }

        .arc-remarks {
            height: calc(1.78in + 4mm);
            padding: 2px 5px 4px;
            overflow: hidden;
        }

        .arc-remarks-title {
            font-size: var(--arc-label-size);
            line-height: 1.05;
        }

        .arc-remarks-lines {
            margin-top: 0.03in;
            font-size: calc(var(--arc-remarks-size) + 2px);
            line-height: var(--arc-remarks-line-height);
        }

        .arc-remark-line {
            display: flex;
            align-items: baseline;
            gap: 0.05in;
        }

        .arc-remark-line[hidden],
        .arc-remark-line.is-empty {
            display: none !important;
        }

        .arc-life-remark-line.is-empty,
        .arc-free-remark-line.is-empty {
            display: flex !important;
        }

        .arc-remark-line.is-fit-excluded {
            display: none !important;
        }

        .arc-remark-line::before {
            content: "* ";
            font-weight: 700;
        }

        .arc-remark-text {
            min-width: 0;
        }

        .arc-remark-line.is-print-disabled .arc-remark-text {
            color: #777;
        }

        .arc-replacement-parts-print-value {
            display: none;
        }

        .arc-replacement-parts-choice {
            display: inline-flex;
            align-items: center;
            gap: 0.18in;
            min-width: 0;
            white-space: nowrap;
        }

        .arc-replacement-parts-choice label {
            display: inline-flex;
            align-items: center;
            gap: 0.035in;
            margin: 0;
            line-height: inherit;
            white-space: nowrap;
        }

        .arc-replacement-parts-choice input {
            width: 13px;
            height: 13px;
            margin: 0;
            flex: 0 0 auto;
        }

        .arc-remark-toggle {
            display: inline-flex;
            align-items: center;
            margin-left: 0.03in;
            transform: translateY(1px);
        }

        .arc-remark-toggle input {
            width: 13px;
            height: 13px;
            margin: 0;
        }

        .arc-remark-date-input {
            width: 1.22in;
            min-width: 1.22in;
            height: 20px;
            border: 1px solid #777;
            border-radius: 2px;
            padding: 0 2px;
            font: inherit;
            line-height: 1.1;
            background: #fff;
        }

        .arc-remark-number-input {
            width: 0.36in;
            min-width: 0.36in;
            height: 20px;
            border: 1px solid #777;
            border-radius: 2px;
            padding: 0 2px;
            font: inherit;
            line-height: 1.1;
            text-align: center;
            background: #fff;
        }

        .arc-manual-revision-date-input {
            width: 1.03in;
            min-width: 1.03in;
        }

        .arc-manual-cmm-extra-input {
            width: auto;
            min-width: 1.2in;
            flex: 1 1 auto;
            border: 0;
            border-radius: 0;
            padding: 3px 8px;
            text-align: left;
        }

        .arc-life-remark-input,
        .arc-free-remark-input {
            min-width: 0;
            flex: 1 1 auto;
            height: 20px;
            border: 0;
            border-bottom: 1px dotted #777;
            border-radius: 0;
            padding: 0 3px;
            color: #000;
            font: inherit;
            line-height: 1;
            outline: 0;
        }

        .arc-life-remark-input {
            width: 33.333%;
            max-width: 33.333%;
            flex: 0 0 33.333%;
        }

        .arc-life-remark-print-value,
        .arc-free-remark-print-value {
            display: none;
            min-width: 0;
        }

        .arc-free-remark-line .arc-remark-toggle {
            flex: 0 0 auto;
            margin-left: 0;
        }

        .arc-form-shell input:not([type="checkbox"]):not([type="radio"]),
        .arc-form-shell select,
        .arc-form-shell [contenteditable="true"] {
            background: #eeeeee !important;
        }

        .arc-remark-print-date {
            display: none;
        }

        .arc-cert-row {
            display: grid;
            grid-template-columns: 46.7% 53.3%;
            height: calc(1.26in + (0.34in + 2mm) + (0.34in + 2mm));
            min-height: 0;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            overflow: hidden;
        }

        .arc-cert-left,
        .arc-cert-right {
            position: relative;
            display: grid;
            min-height: 0;
            grid-template-rows: minmax(0, 1.26in) minmax(0, calc(0.34in + 2mm)) minmax(0, calc(0.34in + 2mm));
            border-right: 1.5px solid #000;
        }

        .arc-cert-right {
            border-right: 0;
        }

        .arc-cert-cross {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 3;
            pointer-events: none;
        }

        .arc-cert-cross line {
            stroke: #000;
            stroke-width: 1.5;
            vector-effect: non-scaling-stroke;
        }

        .arc-cert-main {
            position: relative;
            padding: 2px 5px 3px;
            border-bottom: 1.5px solid #000;
            font-size: var(--arc-label-size);
            line-height: 1.1;
            min-height: 0;
            overflow: hidden;
        }

        .arc-cert-main p {
            margin: 0 0 0.08in;
        }

        .arc-choice {
            display: flex;
            align-items: center;
            gap: 0.13in;
            margin: 0.09in 0 0 0.56in;
            font-size: var(--arc-value-size);
        }

        .arc-cert-right .arc-choice {
            margin-left: 0.58in;
            margin-top: 0.04in;
        }

        .arc-cert-right .arc-cert-main {
            display: grid;
            grid-template-rows: auto auto auto 1fr;
            padding-bottom: 0;
        }

        .arc-cert-main p.arc-release-text {
            align-self: end;
            margin: 0;
            padding-bottom: 2px;
            font-size: var(--arc-label-size);
            line-height: 1.08;
        }

        .arc-check {
            position: relative;
            display: inline-block;
            width: 0.3in;
            height: 0.3in;
            flex: 0 0 0.3in;
            border: 2px solid #000;
        }

        .arc-check.is-checked::before,
        .arc-check.is-checked::after {
            content: "";
            position: absolute;
            left: calc(50% - 1px);
            top: -2px;
            width: 0;
            height: calc(100% + 4px);
            border-left: 2px solid #000;
            transform-origin: center;
        }

        .arc-check.is-checked::before {
            transform: rotate(45deg);
        }

        .arc-check.is-checked::after {
            transform: rotate(-45deg);
        }

        .arc-cert-subgrid {
            display: grid;
            grid-template-columns: 58% 42%;
        }

        .arc-cert-right .arc-cert-subgrid {
            grid-template-columns: 57% 43%;
        }

        .arc-cert-small {
            position: relative;
            border-right: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 2px 4px;
            font-size: var(--arc-label-size);
        }

        .arc-cert-small:nth-child(2n) {
            border-right: 0;
        }

        .arc-cert-subgrid:last-child .arc-cert-small {
            border-bottom: 0;
        }

        .arc-cert-value {
            position: absolute;
            left: 0.08in;
            right: 0.08in;
            bottom: 0.02in;
            text-align: center;
            font-size: var(--arc-value-size);
            line-height: 1.1;
        }

        .arc-cert-signoff-cell .arc-cert-value {
            bottom: 0.075in;
        }

        .arc-cert-control {
            width: calc(100% - 0.16in);
            height: 0.23in;
            border: 0;
            border-bottom: 1px dotted #777;
            border-radius: 0;
            background: transparent;
            color: #000;
            font-family: "Times New Roman", Times, serif;
            font-size: var(--arc-value-size);
            line-height: 1.1;
            outline: 0;
        }

        .arc-cert-manager-control {
            padding: 0 0.18in 0 0.04in;
            text-align: center;
            text-align-last: center;
        }

        .arc-cert-date-control {
            padding: 0 0.27in 0 0.04in;
            text-align: center;
        }

        .arc-cert-date-picker-control {
            left: auto;
            right: 0.07in;
            width: 0.21in;
            padding: 0;
            border-bottom: 0;
            color: transparent;
            font-size: 12px;
            cursor: pointer;
        }

        .arc-cert-date-picker-control::-webkit-datetime-edit {
            display: none;
        }

        .arc-cert-date-picker-control::-webkit-calendar-picker-indicator {
            margin: 0;
            padding: 0;
            cursor: pointer;
        }

        .arc-cert-control[aria-invalid="true"] {
            border-bottom-color: #dc3545;
        }

        .arc-cert-boost-label {
            font-size: calc(var(--arc-label-size) + 1px);
        }

        .arc-cert-13a {
            font-size: var(--arc-label-size);
        }

        .arc-cert-13a .arc-choice,
        .arc-cert-right .arc-choice {
            font-size: calc(var(--arc-value-size) - 2px);
        }

        .arc-cert-boost-label .arc-date-hint {
            font-size: calc(var(--arc-label-size) + 1px);
        }

        .arc-cert-value.arc-cert-boost-value,
        .arc-cert-control.arc-cert-boost-value {
            font-size: calc(var(--arc-value-size) + 2px);
        }

        .arc-cert-print-value {
            display: none;
        }

        .arc-date-hint {
            font-size: var(--arc-label-size);
            opacity: 1;
        }

        .arc-previous {
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: 0.15in;
            min-height: 0;
            border-bottom: 1.5px solid #000;
            font-size: var(--arc-label-size);
            line-height: 1;
        }

        .arc-previous div {
            display: flex;
            align-items: center;
            padding: 2px 3px;
        }

        .arc-previous div:last-child {
            justify-content: flex-end;
            text-align: right;
        }

        .arc-installer {
            flex: 1 1 auto;
            min-height: 0;
            padding: 0.05in 0.04in 0.02in;
            font-size: calc(var(--arc-installer-size) + 1px);
            line-height: 1.08;
            overflow: hidden;
        }

        .arc-installer h2 {
            margin: 0 0 0.04in;
            text-align: center;
            font-size: calc(var(--arc-installer-title-size) + 2px);
            font-weight: 700;
        }

        .arc-installer p {
            margin: 0 0 0.035in;
        }

        @media print {
            :root {
                --arc-print-installer-bottom-clearance: 2mm;
                --arc-print-sheet-height: calc(7.74in + var(--arc-print-installer-bottom-clearance));
                --arc-print-top-height: calc(0.78in - 2mm);
                --arc-print-org-height: 0.88in;
                --arc-print-items-header-height: 0.22in;
                --arc-print-items-row-height: 0.56in;
                --arc-print-items-height: calc(var(--arc-print-items-header-height) + var(--arc-print-items-row-height));
                --arc-print-remarks-height: calc(1.78in + 4mm);
                --arc-print-cert-main-height: 1.26in;
                --arc-print-cert-subrow-height: calc(0.34in + 2mm);
                --arc-print-cert-height: calc(
                    var(--arc-print-cert-main-height)
                    + var(--arc-print-cert-subrow-height)
                    + var(--arc-print-cert-subrow-height)
                );
                --arc-print-previous-height: 0.15in;
                --arc-print-installer-height: calc(
                    var(--arc-print-sheet-height)
                    - var(--arc-print-top-height)
                    - var(--arc-print-org-height)
                    - var(--arc-print-items-height)
                    - var(--arc-print-remarks-height)
                    - var(--arc-print-cert-height)
                    - var(--arc-print-previous-height)
                );
            }

            .arc-toolbar {
                display: none !important;
            }

            .arc-detail-picker {
                display: none !important;
            }

            .arc-form-shell {
                display: block;
            }

            .arc-remark-toggle {
                display: none !important;
            }

            .arc-remark-date-input {
                display: none !important;
            }

            .arc-remark-number-input {
                display: none !important;
            }

            .arc-remark-print-date {
                display: inline !important;
            }

            .arc-life-remark-input,
            .arc-free-remark-input {
                display: none !important;
            }

            .arc-life-remark-print-value,
            .arc-free-remark-print-value {
                display: inline !important;
            }

            .arc-life-remark-line.is-empty,
            .arc-free-remark-line.is-empty {
                display: none !important;
            }

            .arc-remark-line.is-print-disabled {
                display: none !important;
            }

            .arc-replacement-parts-print-value {
                display: inline !important;
            }

            .arc-replacement-parts-choice {
                display: none !important;
            }

            .arc-cert-control {
                display: none !important;
            }

            .arc-status-work-select {
                display: none !important;
            }

            .arc-form-shell input,
            .arc-form-shell select,
            .arc-form-shell [contenteditable] {
                background: transparent !important;
            }

            .arc-cert-print-value {
                display: block !important;
            }

            .arc-status-work-print-value {
                display: block !important;
            }

            .arc-item-secondary-input,
            .arc-item-secondary-toggle {
                display: none !important;
            }

            .arc-item-secondary-print-value {
                display: block !important;
                width: 100%;
                text-align: center;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .arc-item-secondary-line.is-print-disabled,
            .arc-item-secondary-line.is-empty {
                display: none !important;
            }

            html,
            body {
                width: auto;
                height: auto;
                min-height: 0;
                background: #fff;
                margin: 0;
                overflow: hidden;
            }

            body {
                padding: 0;
            }

            .arc-sheet {
                width: 10.36in;
                height: var(--arc-print-sheet-height);
                min-height: 0;
                margin: 0;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                break-inside: avoid;
                page-break-inside: avoid;
                page-break-after: avoid;
            }

            .arc-sheet > .arc-top,
            .arc-sheet > .arc-org,
            .arc-sheet > .arc-items,
            .arc-sheet > .arc-remarks,
            .arc-sheet > .arc-cert-row,
            .arc-sheet > .arc-previous,
            .arc-sheet > .arc-installer {
                flex-grow: 0;
                flex-shrink: 0;
                min-height: 0;
                overflow: hidden;
            }

            .arc-top {
                flex-basis: var(--arc-print-top-height);
                height: var(--arc-print-top-height);
            }

            .arc-org {
                flex-basis: var(--arc-print-org-height);
                height: var(--arc-print-org-height);
            }

            .arc-items {
                flex-basis: var(--arc-print-items-height);
                height: var(--arc-print-items-height);
            }

            .arc-items th {
                height: var(--arc-print-items-header-height);
            }

            .arc-items td {
                height: var(--arc-print-items-row-height);
            }

            .arc-remarks {
                flex-basis: var(--arc-print-remarks-height);
                height: var(--arc-print-remarks-height);
            }

            .arc-remarks-lines {
                font-size: calc(var(--arc-remarks-size) + 2px);
                line-height: 1.22;
            }

            .arc-cert-row {
                flex-basis: var(--arc-print-cert-height);
                height: var(--arc-print-cert-height);
                min-height: 0;
            }

            .arc-cert-left,
            .arc-cert-right {
                height: 100%;
                grid-template-rows: var(--arc-print-cert-main-height) var(--arc-print-cert-subrow-height) var(--arc-print-cert-subrow-height);
            }

            .arc-release-text {
                font-size: var(--arc-label-size);
                line-height: 1.02;
            }

            .arc-cert-small {
                font-size: var(--arc-label-size);
            }

            .arc-date-hint {
                font-size: var(--arc-label-size);
            }

            .arc-cert-value {
                bottom: 0.01in;
                font-size: var(--arc-value-size);
            }

            .arc-cert-signoff-cell .arc-cert-value {
                bottom: 0.075in;
            }

            .arc-cert-boost-label {
                font-size: calc(var(--arc-label-size) + 1px);
            }

            .arc-cert-13a {
                font-size: var(--arc-label-size);
            }

            .arc-cert-13a .arc-choice,
            .arc-cert-right .arc-choice {
                font-size: calc(var(--arc-value-size) - 2px);
            }

            .arc-cert-boost-label .arc-date-hint {
                font-size: calc(var(--arc-label-size) + 1px);
            }

            .arc-cert-value.arc-cert-boost-value,
            .arc-cert-control.arc-cert-boost-value {
                font-size: calc(var(--arc-value-size) + 2px);
            }

            .arc-previous {
                flex-basis: var(--arc-print-previous-height);
                height: var(--arc-print-previous-height);
                min-height: 0;
            }

            .arc-installer {
                flex-basis: var(--arc-print-installer-height);
                height: var(--arc-print-installer-height);
                min-height: 0;
                padding-bottom: 0.02in;
                font-size: calc(var(--arc-installer-size) + 1px);
                line-height: 1.08;
            }

            .arc-installer h2 {
                margin-bottom: 0.04in;
                font-size: calc(var(--arc-installer-title-size) + 2px);
            }

            .arc-installer p {
                margin-bottom: 0.035in;
            }
        }
    </style>
</head>
<body>
<div class="arc-toolbar">
    <button type="button" class="arc-print-button" onclick="window.print()">Print Form</button>
</div>
<div class="arc-form-shell">
<main class="arc-sheet">
    <section class="arc-row arc-top">
        <div class="arc-cell arc-country">
            <div class="arc-label">1. Approving Civil Aviation Authority / Country</div>
            <div class="arc-country-name">Transport Canada</div>
        </div>
        <div class="arc-cell arc-title">
            <div class="arc-label">2.</div>
            <div class="arc-title-main">AUTHORIZED RELEASE CERTIFICATE</div>
            <div class="arc-title-sub">Form One</div>
        </div>
        <div class="arc-cell arc-tracking">
            <div class="arc-label">3. Form Tracking No.</div>
            <div class="arc-tracking-no" data-certificate-tracking-number>{{ $trackingNumber }}</div>
            <div class="arc-handline"></div>
        </div>
    </section>

    <section class="arc-row arc-org">
        <div class="arc-cell arc-org-block">
            <div class="arc-label">4. Organization Name and Address</div>
            <div>
                <img class="arc-logo" src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Aviatechnik Corporation">
            </div>
            <div class="arc-address">
                Aviatechnik Corporation<br>
                710 Gana Court<br>
                Mississauga, ON, Canada, L5S 1P2
            </div>
            <div class="arc-contact">
                Tel: 905-890-7778<br>
                <span class="arc-url">www.aviatechnikcorp.com</span>
            </div>
        </div>
        <div class="arc-cell arc-work-order">
            <div class="arc-label">5. Work Order / Contract / Invoice</div>
            <div
                class="arc-value arc-c-editable"
                role="textbox"
                contenteditable="{{ $selectedCertificateStateKey === 'main:c' ? 'true' : 'false' }}"
                data-certificate-work-order
                data-default-value="{{ $customerPo !== '' ? $customerPo : '-' }}"
                data-original-value="{{ $workOrderText }}"
                aria-label="Work Order / Contract / Invoice"
                spellcheck="false"
            >{{ $workOrderText }}</div>
        </div>
    </section>

    <table class="arc-items" aria-label="Certificate items">
        <colgroup>
            <col class="arc-item-no">
            <col class="arc-description">
            <col class="arc-part">
            <col class="arc-qty">
            <col class="arc-serial">
            <col class="arc-status">
        </colgroup>
        <thead>
        <tr>
            <th>6. Item</th>
            <th>7. Description</th>
            <th>8. Part No.</th>
            <th>9 Qty.</th>
            <th>10. Serial / Batch No.</th>
            <th>11. Status / Work</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>1</td>
            <td class="arc-item-multiline arc-description-cell">
                <div
                    class="arc-item-description-input"
                    role="textbox"
                    contenteditable="true"
                    data-certificate-item-description
                    data-original-value="{{ $itemDescription }}"
                    aria-label="Description"
                    spellcheck="false"
                >{{ $itemDescription }}</div>
            </td>
            <td class="arc-item-identifier-cell">
                <div class="arc-item-identifier-values">
                    <div
                        class="arc-c-editable arc-item-identifier-primary"
                        role="textbox"
                        contenteditable="{{ $selectedCertificateStateKey === 'main:c' ? 'true' : 'false' }}"
                        data-certificate-item-part
                        data-default-value="{{ $partNumber }}"
                        data-original-value="{{ $partNumber }}"
                        aria-label="ASSY Part No."
                        spellcheck="false"
                    >{{ $partNumber }}</div>
                    <div
                        class="arc-item-secondary-line {{ ! $includePartNumberSecondary ? 'is-print-disabled' : '' }} {{ $partNumberSecondary === '' ? 'is-empty' : '' }}"
                        data-certificate-item-part-secondary-line
                    >
                        <input
                            type="text"
                            class="arc-item-secondary-input"
                            value="{{ $partNumberSecondary }}"
                            data-certificate-item-part-secondary
                            data-original-value="{{ $partNumberSecondary }}"
                            aria-label="Part No. of component"
                            spellcheck="false"
                        >
                        <span class="arc-item-secondary-print-value" data-certificate-item-part-secondary-output>{{ $partNumberSecondary }}</span>
                        <label class="arc-item-secondary-toggle" title="Show lower Part No. on print">
                            <input
                                type="checkbox"
                                data-certificate-item-part-secondary-toggle
                                aria-label="Show lower Part No. on print"
                                @checked($includePartNumberSecondary)
                            >
                        </label>
                    </div>
                </div>
            </td>
            <td>1</td>
            <td class="arc-item-identifier-cell">
                <div class="arc-item-identifier-values">
                    <div
                        class="arc-c-editable arc-item-identifier-primary"
                        role="textbox"
                        contenteditable="{{ $selectedCertificateStateKey === 'main:c' ? 'true' : 'false' }}"
                        data-certificate-item-serial
                        data-default-value="{{ $serialNumber }}"
                        data-original-value="{{ $serialNumber }}"
                        aria-label="ASSY Serial / Batch No."
                        spellcheck="false"
                    >{{ $serialNumber }}</div>
                    <div
                        class="arc-item-secondary-line {{ ! $includeSerialNumberSecondary ? 'is-print-disabled' : '' }} {{ $serialNumberSecondary === '' ? 'is-empty' : '' }}"
                        data-certificate-item-serial-secondary-line
                    >
                        <input
                            type="text"
                            class="arc-item-secondary-input"
                            value="{{ $serialNumberSecondary }}"
                            data-certificate-item-serial-secondary
                            data-original-value="{{ $serialNumberSecondary }}"
                            aria-label="Serial / Batch No. of component"
                            spellcheck="false"
                        >
                        <span class="arc-item-secondary-print-value" data-certificate-item-serial-secondary-output>{{ $serialNumberSecondary }}</span>
                        <label class="arc-item-secondary-toggle" title="Show lower Serial No. on print">
                            <input
                                type="checkbox"
                                data-certificate-item-serial-secondary-toggle
                                aria-label="Show lower Serial No. on print"
                                @checked($includeSerialNumberSecondary)
                            >
                        </label>
                    </div>
                </div>
            </td>
            <td class="arc-status-work-cell">
                <select class="arc-status-work-select" data-certificate-status-select aria-label="Status / Work">
                    @foreach($certificateStatusOptions as $statusOption)
                        @php
                            $statusOptionId = (int) ($statusOption->id ?? $statusOption['id'] ?? 0);
                            $statusOptionName = trim((string) ($statusOption->name ?? $statusOption['name'] ?? ''));
                            $statusOptionDisplay = $formatStatusWork($statusOptionName);
                        @endphp
                        @if($statusOptionId > 0 && $statusOptionName !== '')
                            <option
                                value="{{ $statusOptionId }}"
                                data-status-display="{{ $statusOptionDisplay }}"
                                @selected((int) $selectedCertificateInstructionId === $statusOptionId)
                            >
                                {{ $statusOptionDisplay }}
                            </option>
                        @endif
                    @endforeach
                </select>
                <div class="arc-status-work-print-value" data-certificate-status-output>{{ $status }}</div>
            </td>
        </tr>
        </tbody>
    </table>

    <section class="arc-remarks">
        <div class="arc-remarks-title">12. Remarks:</div>
        <div class="arc-remarks-lines">
            @foreach($remarks as $remark)
                @php
                    $remarkText = trim((string) ($remark['text'] ?? ''));
                    $remarkSettingKey = trim((string) ($remark['setting_key'] ?? ''));
                    $remarkChecked = (bool) ($remark['checked'] ?? true);
                    $isLifeRemark = ! empty($remark['life_remark']);
                    $isCorrectionRemark = ! empty($remark['c_correction_remark']);
                    $isStatusRemark = array_key_exists('status_remark_suffix', $remark);
                    $isOverhauledOnRemark = ! empty($remark['overhauled_on_remark']);
                    $isReplacementPartsRemark = ! empty($remark['replacement_parts_remark']);
                    $isAirworthinessRemark = ! empty($remark['airworthiness_remark']);
                    $separateRemarkKey = trim((string) ($remark['separate_remark_key'] ?? ''));
                    $statusRemarkBaseSuffix = (string) ($remark['status_remark_base_suffix'] ?? '');
                    $statusRemarkRevisionPrefix = (string) ($remark['status_remark_revision_prefix'] ?? '');
                    $statusRemarkCmmExtraText = trim((string) ($remark['status_remark_cmm_extra_text'] ?? ''));
                    $replacementPartsOptions = is_array($remark['replacement_parts_options'] ?? null)
                        ? $remark['replacement_parts_options']
                        : [];
                    $replacementPartsSelected = (string) ($remark['replacement_parts_selected'] ?? 'none');
                    $replacementPartsDefault = (string) ($remark['replacement_parts_default'] ?? 'none');
                    $hasStatusRevisionInputs = $isStatusRemark && $statusRemarkRevisionPrefix !== '';
                    $remarkDisplayText = $hasStatusRevisionInputs
                        ? $status . $statusRemarkBaseSuffix
                        : $remarkText;
                    $hideRemarkRow = (! $isLifeRemark && $remarkText === '')
                        || ($isCorrectionRemark && ($selectedCertificateStateKey !== 'main:c' || $remarkText === ''));
                @endphp
                @if($remarkText !== '' || $isLifeRemark || $isCorrectionRemark)
                    <div
                        class="arc-remark-line {{ $isLifeRemark ? 'arc-life-remark-line' : '' }} {{ $hideRemarkRow || ($isLifeRemark && $remarkText === '') ? 'is-empty' : '' }} {{ $remarkSettingKey !== '' && ! $remarkChecked ? 'is-print-disabled' : '' }}"
                        @if($hideRemarkRow) hidden @endif
                        @if($isLifeRemark) data-certificate-life-remark-row @endif
                        @if($isCorrectionRemark) data-certificate-c-correction-row @endif
                        @if($isOverhauledOnRemark) data-certificate-overhauled-on-row @endif
                    >
                        @if($isLifeRemark)
                            <input
                                type="text"
                                class="arc-life-remark-input"
                                value="{{ $remarkDisplayText }}"
                                data-certificate-life-remark
                                data-original-value="{{ $remarkDisplayText }}"
                                aria-label="CSN / CSO remark"
                                spellcheck="false"
                            >
                            <span class="arc-life-remark-print-value" data-certificate-life-remark-output>{{ $remarkDisplayText }}</span>
                        @else
                            <span
                                class="arc-remark-text arc-c-editable {{ $isReplacementPartsRemark ? 'arc-replacement-parts-print-value' : '' }}"
                                role="textbox"
                                contenteditable="{{ $selectedCertificateStateKey === 'main:c' && ! $isStatusRemark && ! $isOverhauledOnRemark && ! $isReplacementPartsRemark ? 'true' : 'false' }}"
                                data-certificate-remark-text
                                data-remark-index="{{ $loop->index }}"
                                data-default-value="{{ $remarkDisplayText }}"
                                data-original-value="{{ $remarkDisplayText }}"
                                aria-label="Remark {{ $loop->iteration }}"
                                spellcheck="false"
                                @if($isStatusRemark)
                                    data-certificate-status-remark
                                    data-remark-suffix="{{ $hasStatusRevisionInputs ? $statusRemarkBaseSuffix : $remark['status_remark_suffix'] }}"
                                @endif
                                @if($isAirworthinessRemark)
                                    data-certificate-airworthiness-remark
                                @endif
                                @if($isReplacementPartsRemark)
                                    data-certificate-replacement-parts-remark
                                    data-default-choice="{{ $replacementPartsDefault }}"
                                @endif
                                @if($separateRemarkKey !== '')
                                    data-certificate-separate-remark-key="{{ $separateRemarkKey }}"
                                @endif
                                @if($isCorrectionRemark)
                                    data-certificate-c-correction-remark
                                @endif
                                @if($isOverhauledOnRemark)
                                    data-certificate-overhauled-on-remark
                                @endif
                            >{{ $isOverhauledOnRemark ? 'Overhauled on' : $remarkDisplayText }}</span>
                        @endif
                        @if($isReplacementPartsRemark)
                            <span class="arc-replacement-parts-choice" data-certificate-replacement-parts-choice>
                                @foreach($replacementPartsOptions as $replacementPartsValue => $replacementPartsText)
                                    <label>
                                        <input
                                            type="radio"
                                            name="certificate_replacement_parts_remark"
                                            value="{{ $replacementPartsValue }}"
                                            data-certificate-replacement-parts-option
                                            data-print-text="{{ $replacementPartsText }}"
                                            @checked($replacementPartsSelected === $replacementPartsValue)
                                        >
                                        <span>{{ $replacementPartsText }}</span>
                                    </label>
                                @endforeach
                            </span>
                        @endif
                        @if($hasStatusRevisionInputs)
                            <span class="arc-status-revision-prefix">{{ $statusRemarkRevisionPrefix }}</span>
                            <span
                                class="arc-remark-print-date"
                                data-certificate-manual-revision-print-number
                            >{{ $manualRevisionNumber }}</span>
                            <input
                                type="text"
                                class="arc-remark-number-input arc-manual-revision-number-input"
                                data-certificate-manual-revision-number
                                data-original-value="{{ $manualRevisionNumberInputValue }}"
                                value="{{ $manualRevisionNumberInputValue }}"
                                aria-label="Manual revision number"
                                spellcheck="false"
                            >
                            <span class="arc-status-revision-dated"> dated </span>
                            <span
                                class="arc-remark-print-date"
                                data-certificate-manual-revision-print-date
                            >{{ $manualDate }}</span>
                            <input
                                type="text"
                                class="arc-remark-date-input arc-manual-revision-date-input"
                                data-certificate-manual-revision-date
                                data-original-value="{{ $manualRevisionDateInputValue }}"
                                data-original-iso="{{ $manualRevisionDateIso }}"
                                value="{{ $manualRevisionDateInputValue }}"
                                aria-label="Manual revision date"
                                spellcheck="false"
                            >
                            <span
                                class="arc-remark-print-date arc-manual-cmm-extra-print"
                                data-certificate-cmm-extra-print
                                data-raw-value="{{ $statusRemarkCmmExtraText }}"
                                @if($statusRemarkCmmExtraText === '') hidden @endif
                            >{{ $statusRemarkCmmExtraText !== '' ? ', ' . $statusRemarkCmmExtraText : '' }}</span>
                            <input
                                type="text"
                                class="arc-remark-number-input arc-manual-cmm-extra-input"
                                data-certificate-cmm-extra-text
                                data-original-value="{{ $statusRemarkCmmExtraText }}"
                                value="{{ $statusRemarkCmmExtraText }}"
                                placeholder="Additional text"
                                aria-label="Additional text after CMM revision date"
                                spellcheck="false"
                            >
                            <span class="arc-status-revision-period">.</span>
                        @endif
                        @if($isOverhauledOnRemark)
                            <span
                                class="arc-remark-print-date"
                                data-certificate-overhauled-on-print-date
                            >{{ $overhauledOnDateDisplay !== '' ? ' ' . $overhauledOnDateDisplay : ' ...................' }}</span>
                            <input
                                type="date"
                                class="arc-remark-date-input"
                                data-certificate-overhauled-on-date
                                value="{{ $overhauledOnDateInputValue }}"
                                aria-label="Overhauled on date"
                            >
                        @endif
                        @if($remarkSettingKey !== '')
                            <label class="arc-remark-toggle" title="Include in print">
                                <input
                                    type="checkbox"
                                    class="arc-remark-print-toggle"
                                    data-setting-key="{{ $remarkSettingKey }}"
                                    aria-label="Include in print"
                                    @checked($remarkChecked)
                                >
                            </label>
                        @endif
                    </div>
                @endif
            @endforeach
            @foreach($certificateFreeRemarks as $freeRemarkIndex => $freeRemark)
                <div
                    class="arc-remark-line arc-free-remark-line {{ ! $freeRemark['checked'] ? 'is-print-disabled' : '' }} {{ $freeRemark['text'] === '' ? 'is-empty' : '' }}"
                    data-certificate-free-remark-row="{{ $freeRemarkIndex }}"
                >
                    <input
                        type="text"
                        class="arc-free-remark-input"
                        value="{{ $freeRemark['text'] }}"
                        data-certificate-free-remark-input
                        data-setting-key="{{ $freeRemark['value_key'] }}"
                        data-original-value="{{ $freeRemark['text'] }}"
                        aria-label="Free remark {{ $freeRemarkIndex }}"
                        spellcheck="false"
                    >
                    <span class="arc-free-remark-print-value" data-certificate-free-remark-output>{{ $freeRemark['text'] }}</span>
                    <label class="arc-remark-toggle" title="Include free remark in print">
                        <input
                            type="checkbox"
                            class="arc-remark-print-toggle"
                            data-setting-key="{{ $freeRemark['include_key'] }}"
                            aria-label="Include free remark {{ $freeRemarkIndex }} in print"
                            @checked($freeRemark['checked'])
                        >
                    </label>
                </div>
            @endforeach
        </div>
    </section>

    <section class="arc-cert-row">
        <div class="arc-cert-left">
            <div class="arc-cert-main arc-cert-13a">
                <p>13a. Certifies that the items identified above were manufactured in conformity to:</p>
                <div class="arc-choice">
                    <span class="arc-check"></span>
                    <span>Approved design data and are in condition for safe operation.</span>
                </div>
                <div class="arc-choice">
                    <span class="arc-check"></span>
                    <span>Non approved design data specified in block 12.</span>
                </div>
            </div>
            <div class="arc-cert-subgrid">
                <div class="arc-cert-small arc-cert-boost-label">13b. Signature</div>
                <div class="arc-cert-small arc-cert-boost-label">13c. Approved Organization Number</div>
            </div>
            <div class="arc-cert-subgrid">
                <div class="arc-cert-small arc-cert-boost-label">13d. Name</div>
                <div class="arc-cert-small arc-cert-boost-label">13e. <span class="arc-date-hint">Date (dd/mmm/yyyy)</span></div>
            </div>
            <svg class="arc-cert-cross" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                <line x1="0" y1="0" x2="100" y2="100"></line>
                <line x1="0" y1="100" x2="100" y2="0"></line>
            </svg>
        </div>

        <div class="arc-cert-right">
            <div class="arc-cert-main">
                <p>14a.</p>
                <div class="arc-choice">
                    <span class="arc-check is-checked"></span>
                    <span>CAR 571.10 Maintenance Release.</span>
                </div>
                <div class="arc-choice">
                    <span class="arc-check"></span>
                    <span>Other regulations specified in block 12.</span>
                </div>
                <p class="arc-release-text">
                    Certifies that unless otherwise specified in block 12, the work identified in block 11 and described in block 12,
                    has been performed in compliance with the Canadian Aviation Regulations.
                </p>
            </div>
            <div class="arc-cert-subgrid">
                <div class="arc-cert-small">14b. Signature</div>
                <div class="arc-cert-small arc-cert-boost-label">
                    14c. Approved Organization Number
                    <div class="arc-cert-value arc-cert-boost-value">50-12</div>
                </div>
            </div>
            <div class="arc-cert-subgrid">
                <div class="arc-cert-small arc-cert-boost-label arc-cert-signoff-cell">
                    14d. Name
                    @if($canEditCertificateManager && $managerOptions->isNotEmpty())
                        <select class="arc-cert-value arc-cert-control arc-cert-manager-control arc-cert-boost-value" id="certificateManagerId" data-certificate-manager-select>
                            @foreach($managerOptions as $managerOption)
                                <option value="{{ $managerOption->id }}" @selected((int) $selectedCertificateManagerId === (int) $managerOption->id)>
                                    {{ $managerOption->selection_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="arc-cert-value arc-cert-print-value arc-cert-manager-value arc-cert-boost-value" data-certificate-manager-output>{{ $certificateManagerName }}</div>
                    @else
                        <div class="arc-cert-value arc-cert-manager-value arc-cert-boost-value" data-certificate-manager-output>{{ $certificateManagerName }}</div>
                    @endif
                </div>
                <div class="arc-cert-small arc-cert-signoff-cell">
                    14e. <span class="arc-date-hint">Date (dd/mmm/yyyy)</span>
                    <input
                        type="text"
                        class="arc-cert-value arc-cert-control arc-cert-date-control"
                        id="certificateDateInput"
                        value="{{ $completedDate }}"
                        placeholder="dd/mmm/yyyy"
                        data-certificate-date-input
                        data-default-date="{{ $defaultCertificateDateInputValue }}"
                        data-default-display="{{ $defaultCertificateDateDisplay }}"
                    >
                    <input
                        type="date"
                        class="arc-cert-value arc-cert-control arc-cert-date-picker-control"
                        value="{{ $certificateDateInputValue }}"
                        data-certificate-date-picker
                        aria-label="Select certificate date"
                    >
                    <div class="arc-cert-value arc-cert-print-value" data-certificate-date-output>{{ $completedDate }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="arc-previous">
        <div>(Previously form 24-0078)</div>
        <div>Important: See notes</div>
    </section>

    <section class="arc-installer">
        <h2>Installer Responsibilities</h2>
        <p>This certificate does not constitute authority to install.</p>
        <p>Installer working in accordance with the national regulations of a country other than that specified in block 1, must ensure that their regulations recognize certifications from the country specified.</p>
        <p>Statements in blocks 13a and 14a do not constitute installation certification. In all cases, the technical record must contain an installation certification issued in accordance with the applicable national regulations before the aircraft may be flown.</p>
    </section>
</main>
<aside class="arc-detail-picker" aria-label="Certificate detail selector">
    @php
        $mainDetailOption = $certificateDetailOptions->firstWhere('key', 'main');
        $mainDetailLabel = trim((string) ($mainDetailOption['label'] ?? ''));
    @endphp
    <div class="arc-detail-picker-header">
        <button
            type="button"
            class="arc-detail-main {{ $selectedCertificateItemSource === 'main' ? 'is-active' : '' }}"
            data-certificate-detail-main
            title="Use main detail"
        >
            {{ $mainDetailLabel !== '' ? $mainDetailLabel : 'Main detail' }}
        </button>
        <label class="arc-detail-toggle" title="Log card details">
            <input type="checkbox" data-certificate-detail-toggle aria-label="Log card details" @checked($certificateDetailOpen ?? false)>
            <span>LC</span>
        </label>
        <label class="arc-detail-toggle" title="Certificate C suffix">
            <input
                type="checkbox"
                data-certificate-tracking-c-toggle
                aria-label="Certificate C suffix"
                @checked($selectedCertificateTrackingMode === 'c')
            >
            <span>C</span>
        </label>
    </div>
    <select
        class="arc-detail-select"
        size="24"
        data-certificate-detail-select
        aria-label="Log card details"
        hidden
    >
        @foreach($certificateDetailOptions->where('key', '!=', 'main') as $detailOption)
            <option value="{{ $detailOption['key'] }}" @selected($selectedCertificateItemSource === $detailOption['key'])>
                {{ $detailOption['label'] !== '' ? $detailOption['label'] : $detailOption['key'] }}
            </option>
        @endforeach
    </select>
</aside>
</div>
<script>
    (function () {
        const certificateStateUrl = @json(route('quality.forms.certificate.state.update', ['workorder' => $current_wo->id]));
        const workorderTopFieldsUrl = @json(route('quality.workorder.top_fields.update', ['workorder' => $current_wo->id]));
        const csrfToken = @json(csrf_token());

        function saveSetting(key, value, options = {}) {
            return fetch(certificateStateUrl, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    key: key,
                    value: value,
                    item_source: options.itemSource || null,
                }),
            }).catch(function (error) {
                console.error('Failed to save certificate setting', error);
            });
        }

        const certificateDetailItems = @json($certificateDetailOptions->keyBy('key')->all());
        const certificateItemSettings = @json($certificateItemSettings);
        const certificateStateDefaults = {
            include_airworthiness_remark: false,
            include_landing_gear_log_card: @json($includeLandingGearLogCard),
            include_royco_service: @json($includeRoycoService),
            include_overhauled_on: @json($includeOverhauledOn),
            certificate_replacement_parts_remark: @json($defaultReplacementPartsRemark),
            certificate_cmm_extra_text: '',
            certificate_manager_id: @json($selectedCertificateManagerId),
            certificate_manager_name: @json($certificateManagerName),
            certificate_date: @json($certificateDateInputValue),
            certificate_overhauled_on_date: '',
        };
        const initialCertificateDetailSource = @json($selectedCertificateItemSource);
        const initialCertificateStateKey = @json($selectedCertificateStateKey);
        const detailMainButton = document.querySelector('[data-certificate-detail-main]');
        const detailToggle = document.querySelector('[data-certificate-detail-toggle]');
        const trackingCToggle = document.querySelector('[data-certificate-tracking-c-toggle]');
        const detailSelect = document.querySelector('[data-certificate-detail-select]');
        const detailTracking = document.querySelector('[data-certificate-tracking-number]');
        const detailDescription = document.querySelector('[data-certificate-item-description]');
        const detailPart = document.querySelector('[data-certificate-item-part]');
        const detailPartSecondary = document.querySelector('[data-certificate-item-part-secondary]');
        const detailPartSecondaryToggle = document.querySelector('[data-certificate-item-part-secondary-toggle]');
        const detailPartSecondaryOutput = document.querySelector('[data-certificate-item-part-secondary-output]');
        const detailSerial = document.querySelector('[data-certificate-item-serial]');
        const detailSerialSecondary = document.querySelector('[data-certificate-item-serial-secondary]');
        const detailSerialSecondaryToggle = document.querySelector('[data-certificate-item-serial-secondary-toggle]');
        const detailSerialSecondaryOutput = document.querySelector('[data-certificate-item-serial-secondary-output]');
        const detailLifeRemark = document.querySelector('[data-certificate-life-remark]');
        const detailLifeRemarkOutput = document.querySelector('[data-certificate-life-remark-output]');
        const freeRemarkInputs = Array.from(document.querySelectorAll('[data-certificate-free-remark-input]'));
        const overhauledOnRemark = document.querySelector('[data-certificate-overhauled-on-remark]');
        const overhauledOnDateInput = document.querySelector('[data-certificate-overhauled-on-date]');
        const overhauledOnPrintDate = document.querySelector('[data-certificate-overhauled-on-print-date]');
        const replacementPartsRemark = document.querySelector('[data-certificate-replacement-parts-remark]');
        const replacementPartsOptions = Array.from(document.querySelectorAll('[data-certificate-replacement-parts-option]'));
        const manualRevisionNumberInput = document.querySelector('[data-certificate-manual-revision-number]');
        const manualRevisionPrintNumber = document.querySelector('[data-certificate-manual-revision-print-number]');
        const manualRevisionDateInput = document.querySelector('[data-certificate-manual-revision-date]');
        const manualRevisionPrintDate = document.querySelector('[data-certificate-manual-revision-print-date]');
        const manualCmmExtraInput = document.querySelector('[data-certificate-cmm-extra-text]');
        const manualCmmExtraPrint = document.querySelector('[data-certificate-cmm-extra-print]');
        const detailWorkOrder = document.querySelector('[data-certificate-work-order]');
        const remarksBox = document.querySelector('.arc-remarks');
        const remarksLines = document.querySelector('.arc-remarks-lines');
        const cCorrectionRow = document.querySelector('[data-certificate-c-correction-row]');
        let remarkTexts = Array.from(document.querySelectorAll('[data-certificate-remark-text]'));
        let currentCertificateDetailKey = initialCertificateDetailSource;
        let currentCertificateStateKey = initialCertificateStateKey;

        function refreshRemarkTexts() {
            remarkTexts = Array.from(document.querySelectorAll('[data-certificate-remark-text]'));
        }

        function isCertificateCMode() {
            return currentCertificateDetailKey === 'main' && trackingCToggle && trackingCToggle.checked;
        }

        function editableValue(element) {
            if (!element) {
                return '';
            }

            return ('value' in element ? element.value : element.textContent || '').trim();
        }

        function setEditableValue(element, value) {
            if (!element) {
                return;
            }

            if ('value' in element) {
                element.value = value || '';
            } else {
                element.textContent = value || '';
            }
            element.dataset.originalValue = value || '';
        }

        function isSourceControlledRemark(element) {
            return Boolean(element && (
                element.hasAttribute('data-certificate-status-remark')
                || element.hasAttribute('data-certificate-life-remark')
                || element.hasAttribute('data-certificate-overhauled-on-remark')
                || element.hasAttribute('data-certificate-replacement-parts-remark')
            ));
        }

        function isGenericSavedRemark(element) {
            return Boolean(element
                && !isSourceControlledRemark(element)
                && !element.hasAttribute('data-certificate-separate-remark-key')
                && !element.hasAttribute('data-certificate-c-correction-remark'));
        }

        function syncRemarkRowVisibility(remark) {
            if (!remark) {
                return;
            }

            const row = remark.closest('.arc-remark-line');
            if (!row) {
                return;
            }

            const isEmpty = editableValue(remark) === '';
            const isCorrectionRemark = remark.hasAttribute('data-certificate-c-correction-remark');
            const shouldHide = isEmpty || (isCorrectionRemark && !isCertificateCMode());

            row.hidden = shouldHide;
            row.classList.toggle('is-empty', shouldHide);
        }

        function syncAllRemarkRowVisibility() {
            refreshRemarkTexts();
            remarkTexts.forEach(syncRemarkRowVisibility);
        }

        function setCEditableEnabled(element, enabled) {
            if (!element) {
                return;
            }

            const isEnabled = enabled && !isSourceControlledRemark(element);

            element.setAttribute('contenteditable', isEnabled ? 'true' : 'false');
            element.setAttribute('aria-disabled', isEnabled ? 'false' : 'true');
        }

        function lifeRemarkTextForItem(item) {
            if (item && Object.prototype.hasOwnProperty.call(item, 'life_remark_text')) {
                return item.life_remark_text || '';
            }

            return certificateDetailItems.main?.life_remark_text || '';
        }

        function setLifeRemarkText(value) {
            if (!detailLifeRemark) {
                return;
            }

            const text = String(value || '').trim();
            const row = detailLifeRemark.closest('.arc-life-remark-line');
            setEditableValue(detailLifeRemark, text);
            if (detailLifeRemarkOutput) {
                detailLifeRemarkOutput.textContent = text;
            }
            if (row) {
                row.hidden = false;
                row.classList.toggle('is-empty', text === '');
            }
        }

        function syncLifeRemarkControl(item) {
            setLifeRemarkText(
                getCertificateStateValue('certificate_life_remark', lifeRemarkTextForItem(item))
            );
        }

        function syncFreeRemarkControls() {
            freeRemarkInputs.forEach(function (input) {
                const settingKey = input.getAttribute('data-setting-key');
                const value = String(getCertificateStateValue(settingKey, '') || '');
                const row = input.closest('.arc-free-remark-line');
                const output = row?.querySelector('[data-certificate-free-remark-output]');
                input.value = value;
                input.dataset.originalValue = value;
                if (output) {
                    output.textContent = value;
                }
                if (row) {
                    row.classList.toggle('is-empty', value === '');
                }
            });
        }

        function replacementPartsDefaultChoice() {
            return replacementPartsRemark?.dataset.defaultChoice
                || certificateStateDefaults.certificate_replacement_parts_remark
                || 'none';
        }

        function normalizeReplacementPartsChoice(value) {
            const choice = String(value || '').trim().toLowerCase();

            return replacementPartsOptions.some((option) => option.value === choice)
                ? choice
                : replacementPartsDefaultChoice();
        }

        function replacementPartsTextFor(choice) {
            const selectedOption = replacementPartsOptions.find((option) => option.value === choice)
                || replacementPartsOptions.find((option) => option.value === replacementPartsDefaultChoice())
                || replacementPartsOptions[0];

            return selectedOption?.dataset.printText || selectedOption?.nextElementSibling?.textContent.trim() || '';
        }

        function setReplacementPartsChoice(value) {
            if (!replacementPartsRemark || replacementPartsOptions.length === 0) {
                return;
            }

            const choice = normalizeReplacementPartsChoice(value);
            replacementPartsOptions.forEach((option) => {
                option.checked = option.value === choice;
            });
            setEditableValue(replacementPartsRemark, replacementPartsTextFor(choice));
            syncRemarkRowVisibility(replacementPartsRemark);
        }

        function syncReplacementPartsChoice() {
            setReplacementPartsChoice(
                getCertificateStateValue('certificate_replacement_parts_remark', replacementPartsDefaultChoice())
            );
        }

        function manualCmmExtraText() {
            return manualCmmExtraInput?.value.trim()
                || manualCmmExtraPrint?.dataset.rawValue
                || '';
        }

        function manualCmmExtraPhrase() {
            const text = manualCmmExtraText();

            return text ? ', ' + text : '';
        }

        function updateManualCmmExtraPrint(value) {
            if (!manualCmmExtraPrint) {
                return;
            }

            const text = String(value || '').trim();
            manualCmmExtraPrint.dataset.rawValue = text;
            manualCmmExtraPrint.textContent = text ? ', ' + text : '';
            manualCmmExtraPrint.hidden = text === '';
        }

        function setManualCmmExtraText(value, updateOriginal = true) {
            const text = String(value || '').trim();
            if (manualCmmExtraInput) {
                manualCmmExtraInput.value = text;
                if (updateOriginal) {
                    manualCmmExtraInput.dataset.originalValue = text;
                }
            }
            updateManualCmmExtraPrint(text);
        }

        function syncManualCmmExtraText() {
            setManualCmmExtraText(
                getCertificateStateValue('certificate_cmm_extra_text', '')
            );
        }

        function overhauledOnRemarkText(value) {
            const display = value ? formatCertificateDate(value) : '';

            return `Overhauled on ${display || '...................'}`;
        }

        function setOverhauledOnDateText(value) {
            if (overhauledOnRemark) {
                overhauledOnRemark.textContent = 'Overhauled on';
                overhauledOnRemark.dataset.defaultValue = 'Overhauled on';
                overhauledOnRemark.dataset.originalValue = 'Overhauled on';
                syncRemarkRowVisibility(overhauledOnRemark);
            }
            if (overhauledOnPrintDate) {
                const display = value ? formatCertificateDate(value) : '';
                overhauledOnPrintDate.textContent = display ? ` ${display}` : ' ...................';
            }
            if (overhauledOnDateInput) {
                overhauledOnDateInput.value = value || '';
            }
        }

        function syncOverhauledOnDateControl() {
            const value = String(getCertificateStateValue('certificate_overhauled_on_date', '') || '');
            setOverhauledOnDateText(value);
        }

        function renumberRemarks() {
            refreshRemarkTexts();
            let maxIndex = -1;
            remarkTexts.forEach(function (remark) {
                const index = Number(remark.getAttribute('data-remark-index'));
                if (Number.isFinite(index) && index >= 0) {
                    maxIndex = Math.max(maxIndex, index);
                }
            });
            remarkTexts.forEach(function (remark, index) {
                const currentIndex = Number(remark.getAttribute('data-remark-index'));
                if (!Number.isFinite(currentIndex) || currentIndex < 0) {
                    maxIndex += 1;
                    remark.setAttribute('data-remark-index', String(maxIndex));
                }
                remark.setAttribute('aria-label', `Remark ${index + 1}`);
            });
        }

        function createUserRemarkAfter(currentRemark) {
            if (!remarksLines) {
                return null;
            }

            const row = document.createElement('div');
            row.className = 'arc-remark-line';
            row.setAttribute('data-certificate-user-remark-row', '');

            const span = document.createElement('span');
            span.className = 'arc-remark-text arc-c-editable';
            span.setAttribute('role', 'textbox');
            span.setAttribute('contenteditable', 'true');
            span.setAttribute('aria-disabled', 'false');
            span.setAttribute('data-certificate-remark-text', '');
            span.setAttribute('data-default-value', '');
            span.setAttribute('data-original-value', '');
            span.setAttribute('spellcheck', 'false');
            row.appendChild(span);

            const currentRow = currentRemark?.closest('.arc-remark-line') || null;
            if (currentRow && currentRow.parentNode === remarksLines) {
                currentRow.insertAdjacentElement('afterend', row);
            } else {
                remarksLines.appendChild(row);
            }

            bindRemarkText(span);
            renumberRemarks();
            fitRemarksText();
            span.focus();

            return span;
        }

        function syncCorrectionRemarkVisibility() {
            if (cCorrectionRow) {
                const correctionText = cCorrectionRow.querySelector('[data-certificate-c-correction-remark]');
                syncRemarkRowVisibility(correctionText);
            }
        }

        function fitRemarksText(printableOnly = false) {
            if (!remarksBox || !remarksLines) {
                return;
            }

            const excludedRows = printableOnly
                ? Array.from(remarksLines.querySelectorAll('.arc-remark-line.is-print-disabled, .arc-remark-line.is-empty'))
                : [];

            excludedRows.forEach(function (row) {
                row.classList.add('is-fit-excluded');
            });

            const baseSize = 14;
            const baseLineHeight = 1.22;
            let size = baseSize;
            document.documentElement.style.setProperty('--arc-remarks-size', `${size}px`);
            document.documentElement.style.setProperty('--arc-remarks-line-height', String(baseLineHeight));

            while (size > 8 && remarksBox.scrollHeight > remarksBox.clientHeight) {
                size -= 0.25;
                const scale = size / baseSize;
                document.documentElement.style.setProperty('--arc-remarks-size', `${size}px`);
                document.documentElement.style.setProperty('--arc-remarks-line-height', String(baseLineHeight * scale));
            }

            excludedRows.forEach(function (row) {
                row.classList.remove('is-fit-excluded');
            });
        }

        function syncDetailSelectVisibility() {
            if (!detailSelect) {
                return;
            }

            detailSelect.hidden = !(detailToggle && detailToggle.checked);
        }

        function getDescriptionValue() {
            if (!detailDescription) {
                return '';
            }

            if ('value' in detailDescription) {
                return detailDescription.value || '';
            }

            return detailDescription.textContent || '';
        }

        function setDescriptionValue(value) {
            if (!detailDescription) {
                return;
            }

            if ('value' in detailDescription) {
                detailDescription.value = value || '';
            } else {
                detailDescription.textContent = value || '';
            }
            detailDescription.dataset.originalValue = value || '';
        }

        function setDescriptionEditingDisabled(disabled) {
            if (!detailDescription) {
                return;
            }

            if ('disabled' in detailDescription) {
                detailDescription.disabled = disabled;
            } else {
                detailDescription.setAttribute('contenteditable', disabled ? 'false' : 'true');
                detailDescription.setAttribute('aria-disabled', disabled ? 'true' : 'false');
            }
        }

        function collapseDetailText(value) {
            return String(value || '').replace(/\s+/g, ' ').trim();
        }

        function detailLabel(item) {
            return [
                item.description,
                [item.part_number, item.part_number_secondary].map(collapseDetailText).filter(Boolean).join(' / '),
                [item.serial_number, item.serial_number_secondary].map(collapseDetailText).filter(Boolean).join(' / '),
            ].map(collapseDetailText).filter(Boolean).join(' | ');
        }

        function updateDetailOptionLabel(key) {
            const item = certificateDetailItems[key];
            if (!item) {
                return;
            }

            item.label = detailLabel(item);
            if (key === 'main' && detailMainButton) {
                detailMainButton.textContent = item.label || 'Main detail';
            }
            if (detailSelect && key !== 'main') {
                const option = Array.from(detailSelect.options).find((candidate) => candidate.value === key);
                if (option) {
                    option.textContent = item.label || key;
                }
            }
        }

        function syncCertificateTrackingNumber() {
            if (!detailTracking) {
                return;
            }

            const item = certificateDetailItems[currentCertificateDetailKey] || certificateDetailItems.main || {};
            const mainTrackingNumber = certificateDetailItems.main?.tracking_number || '';
            const cEnabled = currentCertificateDetailKey === 'main' && trackingCToggle && trackingCToggle.checked;

            detailTracking.textContent = cEnabled
                ? mainTrackingNumber + '-C'
                : (item.tracking_number || mainTrackingNumber);
        }

        function certificateStateKeyFor(detailKey) {
            return detailKey === 'main' && trackingCToggle && trackingCToggle.checked
                ? 'main:c'
                : detailKey;
        }

        function syncCertificateStateKey() {
            currentCertificateStateKey = certificateStateKeyFor(currentCertificateDetailKey);
        }

        function getCertificateItemSetting(sourceKey, settingKey) {
            const itemSettings = certificateItemSettings[sourceKey] || {};
            if (Object.prototype.hasOwnProperty.call(itemSettings, settingKey)) {
                return Boolean(itemSettings[settingKey]);
            }

            return Boolean(certificateStateDefaults[settingKey]);
        }

        function syncCertificateRemarkCheckboxes() {
            syncCertificateStateKey();
            document.querySelectorAll('.arc-remark-print-toggle').forEach(function (checkbox) {
                const key = checkbox.getAttribute('data-setting-key');
                const row = checkbox.closest('.arc-remark-line');
                if (!key) {
                    return;
                }

                checkbox.checked = getCertificateItemSetting(currentCertificateStateKey, key);
                if (row) {
                    row.classList.toggle('is-print-disabled', !checkbox.checked);
                }
            });
        }

        function getCertificateStateValue(settingKey, fallback = '') {
            const itemSettings = certificateItemSettings[currentCertificateStateKey] || {};
            if (Object.prototype.hasOwnProperty.call(itemSettings, settingKey)) {
                return itemSettings[settingKey] ?? fallback;
            }

            return certificateStateDefaults[settingKey] ?? fallback;
        }

        function setCertificateStateValue(settingKey, value) {
            certificateItemSettings[currentCertificateStateKey] = certificateItemSettings[currentCertificateStateKey] || {};
            certificateItemSettings[currentCertificateStateKey][settingKey] = value;
        }

        function splitIdentifierLines(value) {
            return String(value || '')
                .split(/\r\n|\r|\n/)
                .map((line) => line.trim())
                .filter(Boolean);
        }

        function primaryIdentifierStateValue(settingKey, fallback) {
            const lines = splitIdentifierLines(getCertificateStateValue(settingKey, fallback || ''));
            return lines[0] || '';
        }

        function secondaryIdentifierDefault(valueKey, legacyKey, itemDefault) {
            const itemSettings = certificateItemSettings[currentCertificateStateKey] || {};
            if (Object.prototype.hasOwnProperty.call(itemSettings, valueKey)) {
                return itemDefault || '';
            }

            const legacyLines = splitIdentifierLines(itemSettings[legacyKey] || '');
            return legacyLines.length > 1 ? legacyLines.slice(1).join(' ') : (itemDefault || '');
        }

        function syncSecondaryItemControl(input, checkbox, output, valueKey, includeKey, defaultValue) {
            if (!input || !checkbox) {
                return;
            }

            const itemSettings = certificateItemSettings[currentCertificateStateKey] || {};
            const value = String(getCertificateStateValue(valueKey, defaultValue || '') || '');
            const includeOnPrint = Object.prototype.hasOwnProperty.call(itemSettings, includeKey)
                ? Boolean(itemSettings[includeKey])
                : value !== '';
            const line = input.closest('.arc-item-secondary-line');

            input.value = value;
            input.dataset.originalValue = value;
            checkbox.checked = includeOnPrint;
            if (output) {
                output.textContent = value;
            }
            if (line) {
                line.classList.toggle('is-print-disabled', !includeOnPrint);
                line.classList.toggle('is-empty', value === '');
            }
        }

        function syncSecondaryItemControls() {
            const item = certificateDetailItems[currentCertificateDetailKey] || certificateDetailItems.main || {};
            syncSecondaryItemControl(
                detailPartSecondary,
                detailPartSecondaryToggle,
                detailPartSecondaryOutput,
                'certificate_item_part_secondary',
                'include_certificate_item_part_secondary',
                secondaryIdentifierDefault(
                    'certificate_item_part_secondary',
                    'certificate_item_part',
                    item.part_number_secondary || ''
                )
            );
            syncSecondaryItemControl(
                detailSerialSecondary,
                detailSerialSecondaryToggle,
                detailSerialSecondaryOutput,
                'certificate_item_serial_secondary',
                'include_certificate_item_serial_secondary',
                secondaryIdentifierDefault(
                    'certificate_item_serial_secondary',
                    'certificate_item_serial',
                    item.serial_number_secondary || ''
                )
            );
        }

        function syncCertificateManagerControl() {
            if (!managerSelect) {
                return;
            }

            const savedManagerId = String(getCertificateStateValue('certificate_manager_id', '') || '');
            const option = savedManagerId
                ? Array.from(managerSelect.options).find((candidate) => candidate.value === savedManagerId)
                : null;
            if (option) {
                managerSelect.value = savedManagerId;
            } else if (managerSelect.options.length > 0 && managerSelect.selectedIndex < 0) {
                managerSelect.selectedIndex = 0;
            }

            const selectedOption = managerSelect.options[managerSelect.selectedIndex];
            if (managerOutput && selectedOption) {
                managerOutput.textContent = selectedOption.textContent.trim();
            }
        }

        function syncCertificateDateControl() {
            if (!dateInput && !datePicker && !dateOutput) {
                return;
            }

            const value = String(getCertificateStateValue('certificate_date', '') || '');
            if (dateInput) {
                dateInput.value = value ? formatCertificateDate(value) : '';
                dateInput.removeAttribute('aria-invalid');
            }
            if (datePicker) {
                datePicker.value = value || '';
            }
            if (dateOutput) {
                dateOutput.textContent = value
                    ? formatCertificateDate(value)
                    : (dateInput?.getAttribute('data-default-display') || '');
            }
        }

        function syncCertificateEditableFields() {
            const item = certificateDetailItems[currentCertificateDetailKey] || certificateDetailItems.main || {};
            const cMode = isCertificateCMode();

            syncCorrectionRemarkVisibility();
            syncOverhauledOnDateControl();
            setCEditableEnabled(detailWorkOrder, cMode);
            setCEditableEnabled(detailPart, cMode);
            setCEditableEnabled(detailSerial, cMode);
            remarkTexts.forEach((remark) => setCEditableEnabled(remark, cMode));
            syncSecondaryItemControls();
            syncLifeRemarkControl(item);
            syncFreeRemarkControls();

            if (!cMode) {
                setEditableValue(detailWorkOrder, detailWorkOrder?.dataset.defaultValue || '');
                setDescriptionValue(item.description || '');
                setEditableValue(detailPart, item.part_number || '');
                setEditableValue(detailSerial, item.serial_number || '');
                remarkTexts.forEach(function (remark) {
                    if (remark.hasAttribute('data-certificate-overhauled-on-remark')) {
                        setEditableValue(remark, remark.dataset.defaultValue || '');
                    } else if (remark.hasAttribute('data-certificate-c-correction-remark')) {
                        setEditableValue(remark, remark.dataset.defaultValue || '');
                    } else {
                        setEditableValue(remark, remark.dataset.defaultValue || '');
                    }
                    syncRemarkRowVisibility(remark);
                });
                syncReplacementPartsChoice();
                syncManualCmmExtraText();
                fitRemarksText();
                return;
            }

            setEditableValue(detailWorkOrder, String(getCertificateStateValue('certificate_work_order', detailWorkOrder?.dataset.defaultValue || '') || ''));
            setDescriptionValue(String(getCertificateStateValue('certificate_item_description', item.description || '') || ''));
            setEditableValue(detailPart, primaryIdentifierStateValue('certificate_item_part', item.part_number || ''));
            setEditableValue(detailSerial, primaryIdentifierStateValue('certificate_item_serial', item.serial_number || ''));

            const savedRemarks = getCertificateStateValue('certificate_remarks', null);
            remarkTexts.forEach(function (remark) {
                const index = Number(remark.getAttribute('data-remark-index'));
                const defaultValue = remark.dataset.defaultValue || '';
                let value = defaultValue;
                if (remark.hasAttribute('data-certificate-airworthiness-remark')) {
                    value = String(getCertificateStateValue('certificate_airworthiness_remark', defaultValue) || '');
                } else if (remark.hasAttribute('data-certificate-separate-remark-key')) {
                    value = String(getCertificateStateValue(remark.getAttribute('data-certificate-separate-remark-key'), defaultValue) || '');
                } else if (remark.hasAttribute('data-certificate-c-correction-remark')) {
                    value = String(getCertificateStateValue('certificate_c_correction_remark', defaultValue) || '');
                } else if (
                    isGenericSavedRemark(remark)
                    && Array.isArray(savedRemarks)
                    && Object.prototype.hasOwnProperty.call(savedRemarks, index)
                ) {
                    value = String(savedRemarks[index] || '');
                }

                setEditableValue(remark, value);
                syncRemarkRowVisibility(remark);
            });
            syncReplacementPartsChoice();
            syncManualCmmExtraText();
            syncCorrectionRemarkVisibility();

            const savedStatusId = String(getCertificateStateValue('certificate_status_instruction_id', '') || '');
            if (savedStatusId && statusSelect) {
                const option = Array.from(statusSelect.options).find((candidate) => candidate.value === savedStatusId);
                if (option) {
                    statusSelect.value = savedStatusId;
                }
            }

            const statusDisplay = String(
                getCertificateStateValue(
                    'certificate_status_work',
                    statusSelect?.options[statusSelect.selectedIndex]?.getAttribute('data-status-display')
                        || statusSelect?.options[statusSelect.selectedIndex]?.textContent.trim()
                        || statusOutput?.textContent
                        || ''
                ) || ''
            );
            if (statusOutput) {
                statusOutput.textContent = statusDisplay;
            }
            if (statusRemark) {
                const statusRemarkValue = statusDisplay + (statusRemark.getAttribute('data-remark-suffix') || '');
                statusRemark.textContent = statusRemarkValue;
                statusRemark.dataset.defaultValue = statusRemarkValue;
                statusRemark.dataset.originalValue = statusRemarkValue;
                syncRemarkRowVisibility(statusRemark);
            }
        }

        function syncCertificateStateControls() {
            syncCertificateStateKey();
            syncCertificateRemarkCheckboxes();
            syncCertificateManagerControl();
            syncCertificateDateControl();
            syncCertificateEditableFields();
            fitRemarksText();
        }

        function applyCertificateDetail(sourceKey, persist = true) {
            const key = certificateDetailItems[sourceKey] ? sourceKey : 'main';
            const item = certificateDetailItems[key] || certificateDetailItems.main || {};
            currentCertificateDetailKey = key;
            if (key !== 'main' && trackingCToggle && trackingCToggle.checked) {
                trackingCToggle.checked = false;
                if (persist) {
                    saveSetting('certificate_tracking_mode', '');
                }
            }
            syncCertificateTrackingNumber();
            setDescriptionValue(item.description || '');
            if (detailPart) {
                detailPart.textContent = item.part_number || '';
            }
            if (detailSerial) {
                detailSerial.textContent = item.serial_number || '';
            }
            setLifeRemarkText(lifeRemarkTextForItem(item));
            if (detailMainButton) {
                detailMainButton.classList.toggle('is-active', key === 'main');
            }
            if (detailSelect) {
                if (key === 'main') {
                    detailSelect.selectedIndex = -1;
                } else {
                    detailSelect.value = key;
                }
            }
            syncCertificateStateControls();
            if (persist) {
                saveSetting('certificate_item_source', key);
            }
            fitRemarksText();
        }

        async function saveCertificateDescription() {
            if (!detailDescription) {
                return;
            }

            const value = getDescriptionValue().trim();
            const originalValue = detailDescription.dataset.originalValue || '';
            if (value === originalValue) {
                return;
            }

            setDescriptionEditingDisabled(true);
            detailDescription.classList.remove('is-invalid');
            detailDescription.classList.add('is-saving');

            try {
                if (isCertificateCMode()) {
                    setCertificateStateValue('certificate_item_description', value);
                    await saveSetting('certificate_item_description', value, {itemSource: currentCertificateStateKey});
                    detailDescription.dataset.originalValue = value;
                    fitRemarksText();
                    return;
                }

                const currentItem = certificateDetailItems[currentCertificateDetailKey] || certificateDetailItems.main || {};
                const isLogDetail = currentCertificateDetailKey !== 'main' && currentItem.source === 'log';
                const body = isLogDetail
                    ? {
                        field: 'component_name',
                        component_id: currentItem.component_id,
                        value: value,
                    }
                    : {
                        field: 'description',
                        value: value,
                    };
                const response = await fetch(workorderTopFieldsUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });
                const data = await response.json().catch(function () {
                    return {};
                });
                if (!response.ok || data.ok !== true) {
                    throw new Error(data.message || 'Could not update description.');
                }

                detailDescription.dataset.originalValue = value;
                if (isLogDetail) {
                    Object.keys(certificateDetailItems).forEach(function (key) {
                        const item = certificateDetailItems[key];
                        if (item && item.source === 'log' && Number(item.component_id) === Number(currentItem.component_id)) {
                            item.description = value;
                            updateDetailOptionLabel(key);
                        }
                    });
                } else if (certificateDetailItems.main) {
                    certificateDetailItems.main.description = value;
                    updateDetailOptionLabel('main');
                }
            } catch (error) {
                console.error('Failed to update certificate description', error);
                detailDescription.classList.add('is-invalid');
                alert(error.message || 'Could not update description.');
            } finally {
                setDescriptionEditingDisabled(false);
                detailDescription.classList.remove('is-saving');
            }
        }

        function bindCertificateEditable(element, settingKey) {
            if (!element) {
                return;
            }

            element.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    setEditableValue(element, element.dataset.originalValue || '');
                    element.classList.remove('is-invalid');
                    element.blur();
                }
            });

            element.addEventListener('blur', async function () {
                if (!isCertificateCMode()) {
                    return;
                }

                const value = editableValue(element);
                const originalValue = element.dataset.originalValue || '';
                if (value === originalValue) {
                    return;
                }

                element.classList.remove('is-invalid');
                element.classList.add('is-saving');
                try {
                    setCertificateStateValue(settingKey, value);
                    await saveSetting(settingKey, value, {itemSource: currentCertificateStateKey});
                    element.dataset.originalValue = value;
                } catch (error) {
                    console.error('Failed to save certificate field', error);
                    element.classList.add('is-invalid');
                } finally {
                    element.classList.remove('is-saving');
                }
            });
        }

        function bindSecondaryItemControl(input, checkbox, output, valueKey, includeKey) {
            if (!input || !checkbox) {
                return;
            }

            const syncVisualState = function () {
                const value = (input.value || '').trim();
                const line = input.closest('.arc-item-secondary-line');
                if (output) {
                    output.textContent = value;
                }
                if (line) {
                    line.classList.toggle('is-empty', value === '');
                    line.classList.toggle('is-print-disabled', !checkbox.checked);
                }
            };

            const saveValue = async function () {
                const value = (input.value || '').trim();
                input.value = value;
                syncVisualState();
                if (value === (input.dataset.originalValue || '')) {
                    return;
                }

                input.classList.remove('is-invalid');
                input.classList.add('is-saving');
                try {
                    setCertificateStateValue(valueKey, value);
                    await saveSetting(valueKey, value, {itemSource: currentCertificateStateKey});
                    input.dataset.originalValue = value;
                } catch (error) {
                    console.error('Failed to save secondary certificate item field', error);
                    input.classList.add('is-invalid');
                } finally {
                    input.classList.remove('is-saving');
                }
            };

            input.addEventListener('input', syncVisualState);
            input.addEventListener('change', saveValue);
            input.addEventListener('blur', saveValue);
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    input.blur();
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    input.value = input.dataset.originalValue || '';
                    input.classList.remove('is-invalid');
                    syncVisualState();
                    input.blur();
                }
            });

            checkbox.addEventListener('change', function () {
                setCertificateStateValue(includeKey, checkbox.checked);
                syncVisualState();
                saveSetting(includeKey, checkbox.checked, {itemSource: currentCertificateStateKey});
            });
        }

        function bindPersistedRemarkInput(input, output, settingKey) {
            if (!input) {
                return;
            }

            const syncVisualState = function () {
                const value = (input.value || '').trim();
                const row = input.closest('.arc-remark-line');
                if (output) {
                    output.textContent = value;
                }
                if (row) {
                    row.hidden = false;
                    row.classList.toggle('is-empty', value === '');
                }
                fitRemarksText();
            };

            const saveValue = async function () {
                const value = (input.value || '').trim();
                input.value = value;
                syncVisualState();
                if (value === (input.dataset.originalValue || '')) {
                    return;
                }

                input.classList.remove('is-invalid');
                input.classList.add('is-saving');
                try {
                    setCertificateStateValue(settingKey, value);
                    await saveSetting(settingKey, value, {itemSource: currentCertificateStateKey});
                    input.dataset.originalValue = value;
                } catch (error) {
                    console.error('Failed to save certificate remark input', error);
                    input.classList.add('is-invalid');
                } finally {
                    input.classList.remove('is-saving');
                }
            };

            input.addEventListener('input', syncVisualState);
            input.addEventListener('change', saveValue);
            input.addEventListener('blur', saveValue);
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    input.blur();
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    input.value = input.dataset.originalValue || '';
                    input.classList.remove('is-invalid');
                    syncVisualState();
                    input.blur();
                }
            });
        }

        function saveCertificateRemarks() {
            if (!isCertificateCMode()) {
                return Promise.resolve();
            }

            const maxIndex = remarkTexts.reduce(function (max, remark) {
                const index = Number(remark.getAttribute('data-remark-index'));
                return Number.isFinite(index) ? Math.max(max, index) : max;
            }, -1);
            const values = Array.from({length: maxIndex + 1}, () => '');
            remarkTexts.forEach(function (remark) {
                const index = Number(remark.getAttribute('data-remark-index'));
                if (Number.isFinite(index) && index >= 0 && isGenericSavedRemark(remark)) {
                    values[index] = editableValue(remark);
                }
            });
            setCertificateStateValue('certificate_remarks', values);

            return saveSetting('certificate_remarks', values, {itemSource: currentCertificateStateKey});
        }
        function bindRemarkText(remark) {
            if (!remark || remark.dataset.remarkBound === 'true') {
                return;
            }

            remark.dataset.remarkBound = 'true';
            remark.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    if (!isCertificateCMode()) {
                        return;
                    }

                    event.preventDefault();
                    createUserRemarkAfter(remark);
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    setEditableValue(remark, remark.dataset.originalValue || '');
                    remark.classList.remove('is-invalid');
                    remark.blur();
                }
            });

            remark.addEventListener('blur', async function () {
                if (!isCertificateCMode()) {
                    remark.dataset.originalValue = editableValue(remark);
                    fitRemarksText();
                    return;
                }

                const value = editableValue(remark);
                const originalValue = remark.dataset.originalValue || '';
                const separateRemarkKey = remark.hasAttribute('data-certificate-airworthiness-remark')
                    ? 'certificate_airworthiness_remark'
                    : (remark.getAttribute('data-certificate-separate-remark-key')
                        || (remark.hasAttribute('data-certificate-c-correction-remark') ? 'certificate_c_correction_remark' : ''));
                if (separateRemarkKey !== '') {
                    syncRemarkRowVisibility(remark);
                    if (value === originalValue) {
                        fitRemarksText();
                        return;
                    }

                    remark.classList.remove('is-invalid');
                    remark.classList.add('is-saving');
                    try {
                        setCertificateStateValue(separateRemarkKey, value);
                        await saveSetting(separateRemarkKey, value, {itemSource: currentCertificateStateKey});
                        remark.dataset.originalValue = value;
                        fitRemarksText();
                    } catch (error) {
                        console.error('Failed to save certificate remark', error);
                        remark.classList.add('is-invalid');
                    } finally {
                        remark.classList.remove('is-saving');
                    }
                    return;
                }

                if (value === '') {
                    syncRemarkRowVisibility(remark);
                    remark.classList.remove('is-invalid');
                    remark.classList.add('is-saving');
                    try {
                        await saveCertificateRemarks();
                        remarkTexts.forEach((candidate) => {
                            candidate.dataset.originalValue = editableValue(candidate);
                        });
                        fitRemarksText();
                    } catch (error) {
                        console.error('Failed to save certificate remarks', error);
                        remark.classList.add('is-invalid');
                    } finally {
                        remark.classList.remove('is-saving');
                    }
                    return;
                }

                syncRemarkRowVisibility(remark);

                if (value === originalValue) {
                    return;
                }

                remark.classList.remove('is-invalid');
                remark.classList.add('is-saving');
                try {
                    await saveCertificateRemarks();
                    remarkTexts.forEach((candidate) => {
                        candidate.dataset.originalValue = editableValue(candidate);
                    });
                    fitRemarksText();
                } catch (error) {
                    console.error('Failed to save certificate remarks', error);
                    remark.classList.add('is-invalid');
                } finally {
                    remark.classList.remove('is-saving');
                }
            });
        }

        remarkTexts.forEach(bindRemarkText);

        replacementPartsOptions.forEach(function (option) {
            option.addEventListener('change', function () {
                if (!option.checked) {
                    return;
                }

                const choice = normalizeReplacementPartsChoice(option.value);
                setCertificateStateValue('certificate_replacement_parts_remark', choice);
                setReplacementPartsChoice(choice);
                saveSetting('certificate_replacement_parts_remark', choice, {itemSource: currentCertificateStateKey});
                fitRemarksText();
            });
        });

        async function saveManualCmmExtraText() {
            if (!manualCmmExtraInput) {
                return;
            }

            const value = (manualCmmExtraInput.value || '').trim();
            updateManualCmmExtraPrint(value);
            if (value === (manualCmmExtraInput.dataset.originalValue || '')) {
                fitRemarksText();
                return;
            }

            manualCmmExtraInput.disabled = true;
            manualCmmExtraInput.classList.remove('is-invalid');
            manualCmmExtraInput.classList.add('is-saving');
            try {
                setCertificateStateValue('certificate_cmm_extra_text', value);
                await saveSetting('certificate_cmm_extra_text', value, {itemSource: currentCertificateStateKey});
                manualCmmExtraInput.dataset.originalValue = value;
            } catch (error) {
                console.error('Failed to save CMM extra text', error);
                manualCmmExtraInput.classList.add('is-invalid');
            } finally {
                manualCmmExtraInput.disabled = false;
                manualCmmExtraInput.classList.remove('is-saving');
                fitRemarksText();
            }
        }

        if (manualCmmExtraInput) {
            manualCmmExtraInput.addEventListener('input', function () {
                updateManualCmmExtraPrint(manualCmmExtraInput.value);
                fitRemarksText();
            });
            manualCmmExtraInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    manualCmmExtraInput.blur();
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    setManualCmmExtraText(manualCmmExtraInput.dataset.originalValue || '');
                    manualCmmExtraInput.classList.remove('is-invalid');
                    manualCmmExtraInput.blur();
                }
            });
            manualCmmExtraInput.addEventListener('change', saveManualCmmExtraText);
            manualCmmExtraInput.addEventListener('blur', saveManualCmmExtraText);
        }

        bindCertificateEditable(detailWorkOrder, 'certificate_work_order');
        bindCertificateEditable(detailPart, 'certificate_item_part');
        bindCertificateEditable(detailSerial, 'certificate_item_serial');
        bindSecondaryItemControl(
            detailPartSecondary,
            detailPartSecondaryToggle,
            detailPartSecondaryOutput,
            'certificate_item_part_secondary',
            'include_certificate_item_part_secondary'
        );
        bindSecondaryItemControl(
            detailSerialSecondary,
            detailSerialSecondaryToggle,
            detailSerialSecondaryOutput,
            'certificate_item_serial_secondary',
            'include_certificate_item_serial_secondary'
        );
        bindPersistedRemarkInput(
            detailLifeRemark,
            detailLifeRemarkOutput,
            'certificate_life_remark'
        );
        freeRemarkInputs.forEach(function (input) {
            const row = input.closest('.arc-free-remark-line');
            bindPersistedRemarkInput(
                input,
                row?.querySelector('[data-certificate-free-remark-output]'),
                input.getAttribute('data-setting-key')
            );
        });

        if (detailMainButton) {
            detailMainButton.addEventListener('click', function () {
                applyCertificateDetail('main');
                if (detailToggle) {
                    detailToggle.checked = false;
                    syncDetailSelectVisibility();
                }
            });
        }

        if (trackingCToggle) {
            trackingCToggle.addEventListener('change', function () {
                if (trackingCToggle.checked && currentCertificateDetailKey !== 'main') {
                    applyCertificateDetail('main');
                }

                syncCertificateTrackingNumber();
                syncCertificateStateControls();
                fitRemarksText();
                saveSetting('certificate_tracking_mode', trackingCToggle.checked ? 'c' : '');
            });
        }

        if (detailToggle) {
            detailToggle.addEventListener('change', function () {
                syncDetailSelectVisibility();
                saveSetting('certificate_detail_open', detailToggle.checked);
            });
            syncDetailSelectVisibility();
        }

        if (detailSelect) {
            detailSelect.addEventListener('change', function () {
                if (detailSelect.value) {
                    applyCertificateDetail(detailSelect.value);
                }
            });
        }

        if (detailDescription) {
            detailDescription.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    detailDescription.blur();
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    setDescriptionValue(detailDescription.dataset.originalValue || '');
                    detailDescription.classList.remove('is-invalid');
                    detailDescription.blur();
                }
            });
            detailDescription.addEventListener('blur', saveCertificateDescription);
        }

        const managerSelect = document.querySelector('[data-certificate-manager-select]');
        const managerOutput = document.querySelector('[data-certificate-manager-output]');
        if (managerSelect) {
            managerSelect.addEventListener('change', function () {
                const selectedOption = managerSelect.options[managerSelect.selectedIndex];
                if (managerOutput && selectedOption) {
                    managerOutput.textContent = selectedOption.textContent.trim();
                }

                certificateItemSettings[currentCertificateStateKey] = certificateItemSettings[currentCertificateStateKey] || {};
                certificateItemSettings[currentCertificateStateKey].certificate_manager_id = managerSelect.value;
                saveSetting('certificate_manager_id', managerSelect.value, {itemSource: currentCertificateStateKey});
            });
        }

        const statusSelect = document.querySelector('[data-certificate-status-select]');
        const statusOutput = document.querySelector('[data-certificate-status-output]');
        const statusRemark = document.querySelector('[data-certificate-status-remark]');

        function currentStatusDisplay() {
            if (!statusSelect) {
                return statusOutput?.textContent.trim() || '';
            }

            const selectedOption = statusSelect.options[statusSelect.selectedIndex];

            return selectedOption?.getAttribute('data-status-display')
                || selectedOption?.textContent.trim()
                || statusSelect.value;
        }

        function manualRevisionDateDisplay() {
            return manualRevisionDateInput?.value.trim()
                || manualRevisionPrintDate?.textContent.trim()
                || '';
        }

        function manualRevisionNumberDisplay() {
            return manualRevisionNumberInput?.value.trim()
                || manualRevisionPrintNumber?.textContent.trim()
                || '';
        }

        function fullStatusRemarkText(display) {
            if (!statusRemark) {
                return display;
            }

            const suffix = statusRemark.getAttribute('data-remark-suffix') || '';
            if (!manualRevisionDateInput && !manualRevisionPrintDate) {
                return display + suffix;
            }

            const revisionNumber = manualRevisionNumberDisplay();
            const revisionDate = manualRevisionDateDisplay();

            return display + suffix + ', Rev # ' + revisionNumber + ' dated ' + revisionDate + manualCmmExtraPhrase() + '.';
        }

        function setStatusRemarkDisplay(display) {
            if (!statusRemark) {
                return;
            }

            const text = display + (statusRemark.getAttribute('data-remark-suffix') || '');
            statusRemark.textContent = text;
            statusRemark.dataset.defaultValue = text;
            statusRemark.dataset.originalValue = text;
            syncRemarkRowVisibility(statusRemark);
        }

        if (statusSelect) {
            let previousStatusValue = statusSelect.value;
            statusSelect.addEventListener('change', async function () {
                const display = currentStatusDisplay();
                if (statusOutput) {
                    statusOutput.textContent = display;
                }
                setStatusRemarkDisplay(display);

                if (isCertificateCMode()) {
                    setCertificateStateValue('certificate_status_instruction_id', statusSelect.value);
                    setCertificateStateValue('certificate_status_work', display);
                    certificateItemSettings[currentCertificateStateKey] = certificateItemSettings[currentCertificateStateKey] || {};
                    const savedRemarks = certificateItemSettings[currentCertificateStateKey].certificate_remarks;
                    if (Array.isArray(savedRemarks)) {
                        savedRemarks[0] = fullStatusRemarkText(display);
                        setCertificateStateValue('certificate_remarks', savedRemarks);
                        await saveSetting('certificate_remarks', savedRemarks, {itemSource: currentCertificateStateKey});
                    }
                    await saveSetting('certificate_status_instruction_id', statusSelect.value, {itemSource: currentCertificateStateKey});
                    await saveSetting('certificate_status_work', display, {itemSource: currentCertificateStateKey});
                    previousStatusValue = statusSelect.value;
                    fitRemarksText();
                    return;
                }

                statusSelect.disabled = true;
                try {
                    const response = await fetch(workorderTopFieldsUrl, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            field: 'instruction_id',
                            value: statusSelect.value,
                        }),
                    });
                    const data = await response.json().catch(function () {
                        return {};
                    });
                    if (!response.ok || data.ok !== true) {
                        throw new Error(data.message || 'Could not update instruction.');
                    }
                    previousStatusValue = statusSelect.value;
                } catch (error) {
                    console.error('Failed to update certificate instruction', error);
                    statusSelect.value = previousStatusValue;
                    const revertedOption = statusSelect.options[statusSelect.selectedIndex];
                    const revertedDisplay = revertedOption?.getAttribute('data-status-display')
                        || revertedOption?.textContent.trim()
                        || statusSelect.value;
                    if (statusOutput) {
                        statusOutput.textContent = revertedDisplay;
                    }
                    setStatusRemarkDisplay(revertedDisplay);
                    alert(error.message || 'Could not update instruction.');
                } finally {
                    statusSelect.disabled = false;
                }
            });
        }

        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const dateInput = document.querySelector('[data-certificate-date-input]');
        const datePicker = document.querySelector('[data-certificate-date-picker]');
        const dateOutput = document.querySelector('[data-certificate-date-output]');

        function formatCertificateDate(isoDate) {
            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(isoDate || ''));
            if (!match) {
                return '';
            }

            const monthIndex = Number(match[2]) - 1;
            if (monthIndex < 0 || monthIndex >= monthNames.length) {
                return '';
            }

            return `${match[3]}/${monthNames[monthIndex]}/${match[1]}`;
        }

        function parseCertificateDate(value) {
            const text = String(value || '').trim();
            const isoMatch = /^(\d{4})-(\d{2})-(\d{2})$/.exec(text);
            if (isoMatch) {
                return `${isoMatch[1]}-${isoMatch[2]}-${isoMatch[3]}`;
            }

            const displayMatch = /^(\d{1,2})[/. -]([a-z]{3})[/. -](\d{4})$/i.exec(text);
            if (!displayMatch) {
                return '';
            }

            const monthIndex = monthNames.findIndex(function (month) {
                return month.toLowerCase() === displayMatch[2].toLowerCase();
            });
            if (monthIndex === -1) {
                return '';
            }

            return `${displayMatch[3]}-${String(monthIndex + 1).padStart(2, '0')}-${displayMatch[1].padStart(2, '0')}`;
        }

        async function saveManualRevisionNumber() {
            if (!manualRevisionNumberInput) {
                return;
            }

            const value = (manualRevisionNumberInput.value || '').trim();
            if (value === (manualRevisionNumberInput.dataset.originalValue || '')) {
                return;
            }

            manualRevisionNumberInput.disabled = true;
            manualRevisionNumberInput.classList.remove('is-invalid');
            manualRevisionNumberInput.classList.add('is-saving');
            try {
                const response = await fetch(certificateStateUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        key: 'manual_revision_number',
                        value: value,
                    }),
                });
                const data = await response.json().catch(function () {
                    return {};
                });
                if (!response.ok || data.ok !== true) {
                    throw new Error(data.message || 'Could not update manual revision number.');
                }

                const displayValue = data.manual_revision_number || value;
                manualRevisionNumberInput.value = displayValue;
                manualRevisionNumberInput.dataset.originalValue = displayValue;
                if (manualRevisionPrintNumber) {
                    manualRevisionPrintNumber.textContent = displayValue;
                }
                setStatusRemarkDisplay(currentStatusDisplay());
                fitRemarksText();
            } catch (error) {
                console.error('Failed to update manual revision number', error);
                manualRevisionNumberInput.classList.add('is-invalid');
                alert(error.message || 'Could not update manual revision number.');
            } finally {
                manualRevisionNumberInput.disabled = false;
                manualRevisionNumberInput.classList.remove('is-saving');
            }
        }

        async function saveManualRevisionDate() {
            if (!manualRevisionDateInput) {
                return;
            }

            const typedValue = manualRevisionDateInput.value || '';
            const value = parseCertificateDate(typedValue);
            if (!value) {
                manualRevisionDateInput.setAttribute('aria-invalid', 'true');
                return;
            }

            if (
                value === (manualRevisionDateInput.dataset.originalIso || '')
                || formatCertificateDate(value) === (manualRevisionDateInput.dataset.originalValue || '')
            ) {
                manualRevisionDateInput.value = formatCertificateDate(value);
                manualRevisionDateInput.removeAttribute('aria-invalid');
                return;
            }

            manualRevisionDateInput.disabled = true;
            manualRevisionDateInput.classList.remove('is-invalid');
            manualRevisionDateInput.classList.add('is-saving');
            try {
                const response = await fetch(certificateStateUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        key: 'manual_revision_date',
                        value: value,
                    }),
                });
                const data = await response.json().catch(function () {
                    return {};
                });
                if (!response.ok || data.ok !== true) {
                    throw new Error(data.message || 'Could not update manual revision date.');
                }

                const savedValue = data.manual_revision_date || value;
                const displayValue = data.manual_revision_date_display || formatCertificateDate(savedValue);
                manualRevisionDateInput.value = displayValue;
                manualRevisionDateInput.dataset.originalValue = displayValue;
                manualRevisionDateInput.dataset.originalIso = savedValue;
                manualRevisionDateInput.removeAttribute('aria-invalid');
                if (manualRevisionPrintDate) {
                    manualRevisionPrintDate.textContent = displayValue;
                }
                setStatusRemarkDisplay(currentStatusDisplay());
                fitRemarksText();
            } catch (error) {
                console.error('Failed to update manual revision date', error);
                manualRevisionDateInput.classList.add('is-invalid');
                alert(error.message || 'Could not update manual revision date.');
            } finally {
                manualRevisionDateInput.disabled = false;
                manualRevisionDateInput.classList.remove('is-saving');
            }
        }

        if (manualRevisionNumberInput) {
            manualRevisionNumberInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    manualRevisionNumberInput.blur();
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    manualRevisionNumberInput.value = manualRevisionNumberInput.dataset.originalValue || '';
                    manualRevisionNumberInput.classList.remove('is-invalid');
                    manualRevisionNumberInput.blur();
                }
            });
            manualRevisionNumberInput.addEventListener('change', saveManualRevisionNumber);
            manualRevisionNumberInput.addEventListener('blur', saveManualRevisionNumber);
        }

        if (manualRevisionDateInput) {
            manualRevisionDateInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    manualRevisionDateInput.blur();
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    manualRevisionDateInput.value = manualRevisionDateInput.dataset.originalValue || '';
                    manualRevisionDateInput.removeAttribute('aria-invalid');
                    manualRevisionDateInput.classList.remove('is-invalid');
                    manualRevisionDateInput.blur();
                }
            });
            manualRevisionDateInput.addEventListener('change', saveManualRevisionDate);
            manualRevisionDateInput.addEventListener('blur', saveManualRevisionDate);
        }

        if (dateInput) {
            dateInput.addEventListener('change', function () {
                const typedValue = dateInput.value || '';
                const value = parseCertificateDate(typedValue);
                if (dateOutput) {
                    dateOutput.textContent = value
                        ? formatCertificateDate(value)
                        : (dateInput.getAttribute('data-default-display') || '');
                }

                if (value) {
                    dateInput.value = formatCertificateDate(value);
                    if (datePicker) {
                        datePicker.value = value;
                    }
                    dateInput.removeAttribute('aria-invalid');
                } else if (typedValue.trim() !== '') {
                    dateInput.setAttribute('aria-invalid', 'true');
                    return;
                } else if (datePicker) {
                    datePicker.value = '';
                }

                certificateItemSettings[currentCertificateStateKey] = certificateItemSettings[currentCertificateStateKey] || {};
                certificateItemSettings[currentCertificateStateKey].certificate_date = value || '';
                saveSetting('certificate_date', value || null, {itemSource: currentCertificateStateKey});
            });
        }

        if (datePicker) {
            datePicker.addEventListener('change', function () {
                const value = datePicker.value || '';
                if (dateInput) {
                    dateInput.value = value ? formatCertificateDate(value) : '';
                    dateInput.removeAttribute('aria-invalid');
                }
                if (dateOutput) {
                    dateOutput.textContent = value
                        ? formatCertificateDate(value)
                        : (dateInput?.getAttribute('data-default-display') || '');
                }

                certificateItemSettings[currentCertificateStateKey] = certificateItemSettings[currentCertificateStateKey] || {};
                certificateItemSettings[currentCertificateStateKey].certificate_date = value || '';
                saveSetting('certificate_date', value || null, {itemSource: currentCertificateStateKey});
            });
        }

        if (overhauledOnDateInput) {
            overhauledOnDateInput.addEventListener('change', function () {
                const value = overhauledOnDateInput.value || '';
                setCertificateStateValue('certificate_overhauled_on_date', value || '');
                setOverhauledOnDateText(value);
                fitRemarksText();
                saveSetting('certificate_overhauled_on_date', value || null, {itemSource: currentCertificateStateKey});
            });
        }

        applyCertificateDetail(initialCertificateDetailSource, false);
        fitRemarksText();

        window.addEventListener('beforeprint', function () {
            fitRemarksText(true);
        });

        window.addEventListener('afterprint', function () {
            fitRemarksText();
        });

        document.querySelectorAll('.arc-remark-print-toggle').forEach(function (checkbox) {
            const row = checkbox.closest('.arc-remark-line');
            const key = checkbox.getAttribute('data-setting-key');

            function syncRow() {
                if (row) {
                    row.classList.toggle('is-print-disabled', !checkbox.checked);
                }
            }

            syncRow();
            checkbox.addEventListener('change', function () {
                if (key) {
                    certificateItemSettings[currentCertificateStateKey] = certificateItemSettings[currentCertificateStateKey] || {};
                    certificateItemSettings[currentCertificateStateKey][key] = checkbox.checked;
                }
                syncRow();
                if (key) {
                    saveSetting(key, checkbox.checked, {itemSource: currentCertificateStateKey});
                }
            });
        });
    })();
</script>
</body>
</html>

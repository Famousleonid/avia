<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class TdrProcess extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tdrs_id',
        'process_names_id',
        'plus_process', // Дополнительные NDT process_names_id через запятую (например, "2,4")
        'processes',
        'rule_process_ids', // ManualParameterRuleProcess ids that fed this group (for document generation)
        'phase_rule_process_ids', // MasterRulePhaseRuleProcess ids (Start/Finish) — kept separate from Main
        'description',
        'specification_descriptions',
        'requires_fig',
        'requires_zone',
        'notes',
        'repair_order',
        'vendor_id',
        'sort_order', // Поле для сортировки
        'date_start',
        'date_finish',
        'date_promise',
        'date_start_user_id',
        'date_start_user',
        'date_finish_user_id',
        'date_finish_user',
        'working_steps_count',
        'ignore_row',
        'in_traveler',
        'traveler_group',
        'ec',
        'standalone_ec_only', // true = «только EC» (отдельная строка в SP Form); false = companion к Machining/RIL
        'concession_number',  // EC-Finish: OEM concession number / date / authority
        'concession_date',
        'concession_oem',
        'user_id',
    ];
    protected $casts = [
        'processes'        => 'array',
        'specification_descriptions' => 'array',
        'rule_process_ids' => 'array',
        'phase_rule_process_ids' => 'array',
        'date_start'  => 'date',   // <-- важно
        'date_finish' => 'date',   // <-- важно
        'date_promise' => 'date',
        'concession_date' => 'date',
        'ignore_row'  => 'boolean',
        'in_traveler' => 'boolean',
        'traveler_group' => 'integer',
        'standalone_ec_only' => 'boolean',
        'requires_fig' => 'boolean',
        'requires_zone' => 'boolean',
    ];


    public function machiningWorkSteps()
    {
        return $this->hasMany(MachiningWorkStep::class, 'tdr_process_id')->orderBy('step_index');
    }

    public function descriptionForSpecification(int $processId): string
    {
        $descriptions = $this->specification_descriptions ?? [];
        return array_key_exists($processId, $descriptions)
            ? (string) $descriptions[$processId]
            : (string) $this->description;
    }

    public static function selectedSpecificationDescriptions(array $ids, array $descriptions): array
    {
        return array_map(fn ($value) => (string) $value, array_intersect_key($descriptions, array_flip(self::normalizeStoredProcessIds($ids))));
    }

    /**
     * ID процессов из JSON-колонки `processes` (строка после cast и лишний json_encode при сохранении в контроллере).
     *
     * @return list<int>
     */
    public static function normalizeStoredProcessIds(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            $out = [];
            foreach ($raw as $item) {
                if (is_array($item) && array_key_exists('id', $item)) {
                    $id = (int) $item['id'];
                    if ($id > 0) {
                        $out[] = $id;
                    }

                    continue;
                }
                $id = (int) $item;
                if ($id > 0) {
                    $out[] = $id;
                }
            }

            return array_values(array_unique($out));
        }

        if (is_string($raw)) {
            $trim = trim($raw);
            if ($trim === '' || strcasecmp($trim, 'null') === 0) {
                return [];
            }

            $decoded = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded !== null && $decoded !== $trim) {
                return self::normalizeStoredProcessIds($decoded);
            }

            if (ctype_digit($trim)) {
                $id = (int) $trim;

                return $id > 0 ? [$id] : [];
            }

            return [];
        }

        if (is_int($raw) || is_float($raw)) {
            $id = (int) $raw;

            return $id > 0 ? [$id] : [];
        }

        return [];
    }

    // Отношение к модели Tdr
    public function tdr()
    {
        return $this->belongsTo(Tdr::class, 'tdrs_id');
    }

    // Отношение к модели ProcessName
    public function processName()
    {
        return $this->belongsTo(ProcessName::class, 'process_names_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Строка NDT с «плюсом» (например NDT-1 + NDT-4 в одной строке через plus_process).
     * Для групповых форм это одна логическая операция, не несколько.
     */
    public function isCombinedNdtPrimaryRow(): bool
    {
        if (!$this->processName) {
            return false;
        }
        $pn = $this->processName;

        return ($pn->process_sheet_name ?? '') === 'NDT'
            && str_starts_with((string) ($pn->identityName() ?? ''), 'NDT-')
            && trim((string) ($this->plus_process ?? '')) !== '';
    }

    public function missingDescriptionRequirements(?int $specificationId = null): array
    {
        $description = $specificationId === null ? (string) $this->description : $this->descriptionForSpecification($specificationId);
        $missing = [];

        if ($this->requires_fig && stripos($description, 'FIG') === false) {
            $missing[] = 'FIG';
        }

        if ($this->requires_zone && stripos($description, 'ZONE') === false) {
            $missing[] = 'ZONE';
        }

        return $missing;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tdr_process')
            ->logOnly([
                'tdrs_id',
                'process_names_id',
                'plus_process',
                'processes',
                'description',
                'specification_descriptions',
                'notes',
                'date_start',
                'date_finish',
                'date_promise',
                'repair_order',
                'vendor_id',
                'date_start_user',
                'date_finish_user',
                'ignore_row',
                'in_traveler',
                'traveler_group',
                'user_id',
            ])
            ->logOnlyDirty()                // логировать ТОЛЬКО изменившиеся поля
            ->dontSubmitEmptyLogs();        // не создавать пустые записи
    }
    public function tapActivity(Activity $activity, string $eventName)
    {
        if (auth()->check()) {
            $activity->causer()->associate(auth()->user());
        }
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dateStartUpdatedBy()
    {
        return $this->belongsTo(User::class, 'date_start_user_id');
    }

    public function dateFinishUpdatedBy()
    {
        return $this->belongsTo(User::class, 'date_finish_user_id');
    }
}

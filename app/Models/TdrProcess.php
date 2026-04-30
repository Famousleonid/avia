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
        'description',
        'notes',
        'repair_order',
        'vendor_id',
        'sort_order', // Поле для сортировки
        'date_start',
        'date_finish',
        'date_start_user_id',
        'date_finish_user_id',
        'working_steps_count',
        'ignore_row',
        'in_traveler',
        'ec',
        'standalone_ec_only', // true = «только EC» (отдельная строка в SP Form); false = companion к Machining/RIL
        'user_id',
    ];
    protected $casts = [
        'processes'   => 'array',
        'date_start'  => 'date',   // <-- важно
        'date_finish' => 'date',   // <-- важно
        'ignore_row'  => 'boolean',
        'in_traveler' => 'boolean',
        'standalone_ec_only' => 'boolean',
    ];


    public function machiningWorkSteps()
    {
        return $this->hasMany(MachiningWorkStep::class, 'tdr_process_id')->orderBy('step_index');
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
            && str_starts_with((string) ($pn->name ?? ''), 'NDT-')
            && trim((string) ($this->plus_process ?? '')) !== '';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tdr_process')
            ->logOnly([
                'date_start',
                'date_finish',
                'repair_order',
                'vendor_id',
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

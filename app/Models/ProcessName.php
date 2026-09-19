<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessName extends Model
{
    use HasFactory;

    /** Ключ группы групповых форм: Machining + Machining (EC) — одна печать. */
    public const GROUP_KEY_MERGE_MACHINING_MEC = 'MERGE:MACHINING_MEC';

    /** Имена process_names, которые в групповых формах считаются одной группой «обработка». */
    public const MACHINING_EC_MERGE_NAMES = ['Machining', 'Machining (EC)', 'Machining(EC)'];
    public const SYSTEM_TRAVELER_NAME = 'Traveler';

    private const MANUAL_DATE_EDITABLE_NAME_KEYS = [
        'machining',
        'machiningec',
        'quarantine',
        'stressrelief',
        'paint',
        'anodizingat',
        'stdstressrelieflist',
        'stdpaintlist',
    ];

    protected $fillable = [
        'sp_sort_order',
        'name','code','process_sheet_name','form_number','std_days', 'notify_user_id','print_form','show_in_process_picker','sequence_exempt',
        'stage','scope', // EC gate / plan structure: start|prep|ndt|post|finish ; point|part
        'plan_order',    // merger tie-break among ready nodes: lower = earlier (null = 100); Machining=10 (in-house first)
    ];
    public $timestamps = false;

    protected $casts = [
        'sp_sort_order' => 'integer',
        'show_in_process_picker' => 'boolean',
        'print_form' => 'boolean',
        'sequence_exempt' => 'boolean',
    ];

    public function scopeForPicker($query)
    {
        return $query
            ->where('show_in_process_picker', true)
            ->where('name', '!=', self::SYSTEM_TRAVELER_NAME);
    }

    public function scopeInSpFormOrder($query)
    {
        return $query->orderByRaw('sp_sort_order IS NULL')
            ->orderBy('sp_sort_order')->orderBy('id');
    }

    public function scopeIncludedInSpForm($query)
    {
        return $query->where(fn ($q) => $q->whereNull('sp_sort_order')->orWhere('sp_sort_order', '>', 0));
    }

    public function moveToSpPosition(?int $position): array
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($position) {
            $rows = static::query()->inSpFormOrder()->lockForUpdate()->get();
            $moving = $rows->firstWhere('id', $this->id);
            $ordered = $rows->reject(fn ($row) => $row->id === $this->id || $row->sp_sort_order === 0)->values();
            if ($position !== null && $position > 0) {
                $ordered->splice(min($position - 1, $ordered->count()), 0, [$moving]);
            }
            $updates = $rows->whereStrict('sp_sort_order', 0)
                ->reject(fn ($row) => $row->id === $this->id)
                ->map(fn ($row) => ['id' => $row->id, 'value' => 0])->values()->all();
            foreach ($ordered as $index => $row) {
                $row->sp_sort_order = $index + 1;
                $row->save();
                $updates[] = ['id' => $row->id, 'value' => $row->sp_sort_order];
            }
            if ($position === null || $position === 0) {
                $moving->sp_sort_order = $position;
                $moving->save();
                $updates[] = ['id' => $moving->id, 'value' => $position];
            }
            $this->sp_sort_order = $moving->sp_sort_order;

            return $updates;
        });
    }

    public function isSequenceExempt(): bool
    {
        return (bool) ($this->sequence_exempt ?? false);
    }

    public function allowsManualDateEditing(): bool
    {
        return self::allowsManualDateEditingForName($this->name);
    }

    public static function allowsManualDateEditingForName(?string $name): bool
    {
        $key = self::normalizedNameKey($name);

        return in_array($key, self::MANUAL_DATE_EDITABLE_NAME_KEYS, true);
    }

    public static function isExactEcName(?string $name): bool
    {
        return self::normalizedNameKey($name) === 'ec';
    }

    public static function normalizedNameKey(?string $name): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower((string) $name)) ?? '';
    }

    public function processes()
    {
        return $this->hasMany(Process::class, 'process_names_id');
    }

    public function notifyUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'notify_user_id');
    }

    /**
     * No printable process form for this process name.
     */
    public static function hasNoProcessForm(?self $processName): bool
    {
        return $processName === null || ! (bool) $processName->print_form;
    }

    public static function canPrintProcessForm(?self $processName): bool
    {
        return ! self::hasNoProcessForm($processName)
            && trim((string) ($processName->process_sheet_name ?? '')) !== '';
    }

    public static function isMachiningMachiningEcMergeMember(?self $processName): bool
    {
        return $processName !== null && in_array($processName->name, self::MACHINING_EC_MERGE_NAMES, true);
    }

    /** Печатная форма с листом MACHINING (Machining, Machining (EC), Machining (Blend) и т.д.). */
    public static function isMachiningPrintedForm(?self $processName): bool
    {
        return $processName !== null && $processName->process_sheet_name === 'MACHINING';
    }

    /**
     * @return list<int>
     */
    public static function machiningMachiningEcMergeProcessNameIds(): array
    {
        return self::query()
            ->whereIn('name', self::MACHINING_EC_MERGE_NAMES)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Для URL и заголовка группы: предпочитаем запись «Machining».
     */
    public static function machiningMachiningEcRepresentative(): ?self
    {
        $main = self::where('name', 'Machining')->first();
        if ($main) {
            return $main;
        }

        return self::where('name', 'Machining (EC)')->first()
            ?? self::where('name', 'Machining(EC)')->first();
    }

    /**
     * Ключ группы для модалки групповых форм — по записи process_names (name уникален по смыслу операции: «Chrome plate» и «Chrome stripping» не сливаются).
     * Исключение: Machining и Machining (EC) объединяются.
     *
     * @param  bool  $collapseNdtToGroup  true — вкладка всех деталей заказа: все NDT в NDT_GROUP; false — Part Processes: каждый process_name_id отдельно.
     * @return string|int
     */
    public static function groupFormsGroupKey(self $processName, bool $collapseNdtToGroup = false)
    {
        if ($collapseNdtToGroup && $processName->process_sheet_name === 'NDT') {
            return 'NDT_GROUP';
        }

        if (self::isMachiningMachiningEcMergeMember($processName)) {
            return self::GROUP_KEY_MERGE_MACHINING_MEC;
        }

        return $processName->id;
    }

}

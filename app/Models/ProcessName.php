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
    public const MACHINING_EC_MERGE_NAMES = ['Machining', 'Machining (EC)'];

    protected $fillable = [
        'name','process_sheet_name','form_number','std_days', 'notify_user_id','print_form','show_in_process_picker',
    ];
    public $timestamps = false;

    protected $casts = [
        'show_in_process_picker' => 'boolean',
        'print_form' => 'boolean',
    ];

    public function scopeForPicker($query)
    {
        return $query->where('show_in_process_picker', true);
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
     * Нет печатной / групповой формы (EC — служебная строка маршрута, не отправляется на цех).
     */
    public static function hasNoProcessForm(?self $processName): bool
    {
        return $processName !== null && $processName->name === 'EC';
    }

    public static function isMachiningMachiningEcMergeMember(?self $processName): bool
    {
        return $processName !== null && in_array($processName->name, self::MACHINING_EC_MERGE_NAMES, true);
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

        return self::where('name', 'Machining (EC)')->first();
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

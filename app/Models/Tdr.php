<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Tdr extends Model
{
    use HasFactory, LogsActivity;

    public const TYPE_COMPONENT_TDR = 'component_tdr';
    public const TYPE_UNIT_INSPECTION = 'unit_inspection';
    public const TYPE_STD_LIST_CARRIER = 'std_list_carrier';
    public const TYPE_ORDER_NEW = 'order_new';
    public const TYPE_MANUFACTURE_ORDER = 'manufacture_order';
    public const TYPE_MANUFACTURE_REPAIR = 'manufacture_repair';
    public const TYPE_TRANSFER_CLONE = 'transfer_clone';
    public const TYPE_UNKNOWN = 'unknown';

    public const RESULT_SCRAPPED = 'scrapped';

    public const RESULT_OPTIONS = [
        self::RESULT_SCRAPPED,
    ];

    public const TYPE_OPTIONS = [
        self::TYPE_COMPONENT_TDR,
        self::TYPE_UNIT_INSPECTION,
        self::TYPE_STD_LIST_CARRIER,
        self::TYPE_ORDER_NEW,
        self::TYPE_MANUFACTURE_ORDER,
        self::TYPE_MANUFACTURE_REPAIR,
        self::TYPE_TRANSFER_CLONE,
        self::TYPE_UNKNOWN,
    ];

    protected $fillable = [
        'tdr_type',
        'workorder_id',
        'component_id',
        'order_component_id',
        'order_component_assembly_id',
        'serial_number',
        'assy_serial_number',
        'codes_id',
        'conditions_id',
        'necessaries_id',
        'description',
        'qty',
        'po_num',
        'received',
        'use_tdr',
        'use_process_forms',
        'result_status',
        'scrap_reason',
        'replaced_by_tdr_id',
    ];

    protected $casts = [
        'received'           => 'date',
        'use_tdr'            => 'boolean',
        'use_process_forms'  => 'boolean',
        'replaced_by_tdr_id' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tdr')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function scopeForWorkorder($query, int $workorderId)
    {
        return $query->where('workorder_id', $workorderId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('tdr_type', $type);
    }

    public function scopeComponentTdrs($query)
    {
        return $query->where('tdr_type', self::TYPE_COMPONENT_TDR);
    }

    public function scopeUnitInspections($query)
    {
        return $query->where('tdr_type', self::TYPE_UNIT_INSPECTION);
    }

    public function scopeStdListCarriers($query)
    {
        return $query->where('tdr_type', self::TYPE_STD_LIST_CARRIER);
    }

    public function scopeOrderNewRows($query)
    {
        return $query->where('tdr_type', self::TYPE_ORDER_NEW);
    }

    public function scopeManufactureOrderRows($query)
    {
        return $query->where('tdr_type', self::TYPE_MANUFACTURE_ORDER);
    }

    public function scopeManufactureRepairRows($query)
    {
        return $query->where('tdr_type', self::TYPE_MANUFACTURE_REPAIR);
    }

    public function scopeUnknownRows($query)
    {
        return $query->where('tdr_type', self::TYPE_UNKNOWN);
    }

    public function scopeLegacyBlankWorkorderRows($query)
    {
        return $query->where(function ($query): void {
            $query->whereNull('tdr_type')->orWhere('tdr_type', self::TYPE_UNKNOWN);
        })
            ->whereNull('component_id')
            ->whereNull('order_component_id')
            ->whereNull('codes_id')
            ->whereNull('conditions_id')
            ->whereNull('necessaries_id')
            ->where(function ($query): void {
                $query->whereNull('description')->orWhere('description', '');
            });
    }

    public function isComponentTdr(): bool
    {
        return $this->tdr_type === self::TYPE_COMPONENT_TDR;
    }

    public function isUnitInspection(): bool
    {
        return $this->tdr_type === self::TYPE_UNIT_INSPECTION;
    }

    public function isStdListCarrier(): bool
    {
        return $this->tdr_type === self::TYPE_STD_LIST_CARRIER;
    }

    public function isOrderNew(): bool
    {
        return $this->tdr_type === self::TYPE_ORDER_NEW;
    }

    public function isManufactureOrder(): bool
    {
        return $this->tdr_type === self::TYPE_MANUFACTURE_ORDER;
    }

    public function isManufactureRepair(): bool
    {
        return $this->tdr_type === self::TYPE_MANUFACTURE_REPAIR;
    }

    public function isUnknownType(): bool
    {
        return $this->tdr_type === self::TYPE_UNKNOWN;
    }

    public function inferType(?string $manufactureCodeId = null, ?string $orderNewNecessaryId = null, ?string $repairNecessaryId = null): string
    {
        $description = trim((string) $this->description);
        if ($this->component_id === null && $description === 'STD List carrier') {
            return self::TYPE_STD_LIST_CARRIER;
        }

        if ($this->component_id === null && $this->conditions_id !== null) {
            return self::TYPE_UNIT_INSPECTION;
        }

        $codeId = $this->codes_id !== null ? (string) $this->codes_id : null;
        $necessaryId = $this->necessaries_id !== null ? (string) $this->necessaries_id : null;

        if ($manufactureCodeId !== null && $codeId === $manufactureCodeId) {
            if ($orderNewNecessaryId !== null && $necessaryId === $orderNewNecessaryId) {
                return self::TYPE_MANUFACTURE_ORDER;
            }

            if ($repairNecessaryId !== null && $necessaryId === $repairNecessaryId) {
                return self::TYPE_MANUFACTURE_REPAIR;
            }
        }

        if ($this->component_id !== null && $orderNewNecessaryId !== null && $necessaryId === $orderNewNecessaryId) {
            return self::TYPE_ORDER_NEW;
        }

        if ($this->component_id !== null) {
            return self::TYPE_COMPONENT_TDR;
        }

        return self::TYPE_UNKNOWN;
    }

    public function workorder()
    {
        return $this->belongsTo(Workorder::class, 'workorder_id');
    }
    //
    public function component()
    {
        return $this->belongsTo(Component::class);
    }

    public function orderComponent()
    {
        return $this->belongsTo(Component::class, 'order_component_id');
    }

    public function orderComponentAssembly()
    {
        return $this->belongsTo(ComponentAssembly::class, 'order_component_assembly_id');
    }

    public function conditions()
    {
        return $this->belongsTo(Condition::class, 'conditions_id');
    }

    public function necessaries()
    {
        return $this->belongsTo(Necessary::class, 'necessaries_id');
    }

    public function codes()
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }
    public function tdrProcesses()
    {
        return $this->hasMany(TdrProcess::class, 'tdrs_id')->orderBy('sort_order');
    }

    /** The replacement "Order New" TDR created after this component was scrapped. */
    public function replacedByTdr()
    {
        return $this->belongsTo(Tdr::class, 'replaced_by_tdr_id');
    }

    /** The original component TDR that this "Order New" TDR replaces (inverse). */
    public function replacesScrapTdr()
    {
        return $this->hasOne(Tdr::class, 'replaced_by_tdr_id');
    }

    public function isScrapped(): bool
    {
        return $this->result_status === self::RESULT_SCRAPPED;
    }
}




<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;
    protected $fillable = [
        'tdr_id',
        'workorder_id',
        'workorder_source',
        'component_id',
        'component_sn',
        'cloned_tdr_id',
        'reason',
        'unit_on_po',
    ];

    /**
     * WO-приёмник: куда деталь ПРИХОДИТ (получает компонент).
     */
    public function workorder()
    {
        return $this->belongsTo(Workorder::class, 'workorder_id');
    }

    /**
     * WO-источник: откуда деталь БЕРЁТСЯ (отдаёт компонент).
     */
    public function workorderSource()
    {
        return $this->belongsTo(Workorder::class, 'workorder_source');
    }

    /**
     * Исходный TDR в WO-приёмнике, из которого создан перевод (origin).
     */
    public function originTdr()
    {
        return $this->belongsTo(Tdr::class, 'tdr_id');
    }

    /**
     * Клон-TDR, созданный этим переводом в WO-источнике (явная связь
     * вместо угадывания по совпадению полей).
     */
    public function clonedTdr()
    {
        return $this->belongsTo(Tdr::class, 'cloned_tdr_id');
    }

    /**
     * Компонент, который переводится
     */
    public function component()
    {
        return $this->belongsTo(Component::class, 'component_id');
    }

    /**
     * Причина для перевода
     */
    public function reasonCode()
    {
        return $this->belongsTo(Code::class, 'reason');
    }
}

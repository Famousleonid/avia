<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;
    protected $fillable = [
        'workorder_id',
        'workorder_source',
        'component_id',
        'component_sn',
        'reason',
        'unit_on_po',
    ];

    /**
     * Текущий workorder (куда отправляется компонент)
     */
    public function workorder()
    {
        return $this->belongsTo(Workorder::class, 'workorder_id');
    }

    /**
     * Целевой workorder (откуда отправляется компонент)
     */
    public function workorderSource()
    {
        return $this->belongsTo(Workorder::class, 'workorder_source');
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

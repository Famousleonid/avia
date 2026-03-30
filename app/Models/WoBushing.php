<?php

namespace App\Models;

use App\Services\WoBushingRelationalSync;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WoBushing extends Model
{
    use HasFactory;

    protected $fillable = [
        'workorder_id',
    ];

    public function workorder()
    {
        return $this->belongsTo(Workorder::class);
    }

    public function lines()
    {
        return $this->hasMany(WoBushingLine::class, 'wo_bushing_id')->orderBy('sort_order');
    }

    /**
     * Данные для отображения (массив bushing / qty / processes) из нормализованных таблиц.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolvedBushData(): array
    {
        return app(WoBushingRelationalSync::class)->resolveBushDataForViews($this);
    }
}

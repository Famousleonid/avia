<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'workorder_id',
        'component_id',
        'processes', // JSON-поле для хранения массива процессов
        'sort_order', // Поле для сортировки
        'qty',
    ];

    // Автоматическое преобразование JSON в массив
    protected $casts = [
        'processes' => 'array',
    ];

    public function workorder()
    {
        return $this->belongsTo(Workorder::class, 'workorder_id');
    }

    public function component()
    {
        return $this->belongsTo(Component::class, 'component_id');
    }
}

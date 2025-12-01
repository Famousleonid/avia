<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TdrProcess extends Model
{
    use HasFactory;

    // Поля, которые можно массово назначать
    protected $fillable = [
        'tdrs_id',
        'process_names_id',
        'processes', // JSON-поле для хранения массива процессов
        'description',
        'sort_order', // Поле для сортировки
        'date_start',
        'date_finish',
        'ec',// Boolean поле для EC
    ];
    protected $casts = [
        'processes'   => 'array',
        'date_start'  => 'date',   // <-- важно
        'date_finish' => 'date',   // <-- важно
    ];


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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    protected $fillable = ['process_names_id', 'process'];

    // Связь с ProcessName
    public function process_name()
    {
        return $this->belongsTo(ProcessName::class, 'process_names_id');
    }

    // Связь с Manual через промежуточную таблицу manual_processes
    public function manuals()
    {
        return $this->belongsToMany(Manual::class, 'manual_processes', 'processes_id', 'manual_id');
    }
}

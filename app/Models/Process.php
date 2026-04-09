<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Process extends Model
{
    use LogsActivity;

    protected $fillable = ['process_names_id', 'process'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('process')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Связь с ProcessName
    public function process_name()
    {
        return $this->belongsTo(ProcessName::class, 'process_names_id');
    }

    public function manualProcesses()
    {
        return $this->hasMany(ManualProcess::class, 'processes_id');
    }
    // Связь с Manual через промежуточную таблицу manual_processes
    public function manuals()
    {
        return $this->belongsToMany(Manual::class, 'manual_processes', 'processes_id', 'manual_id');
    }

    public function tdrs()
    {
        return $this->belongsTo(Tdr::class);
    }
}

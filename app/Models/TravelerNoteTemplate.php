<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TravelerNoteTemplate extends Model
{
    use LogsActivity;

    protected $fillable = ['manual_id', 'part_number', 'process_names_id', 'notes'];

    public function manual()
    {
        return $this->belongsTo(Manual::class);
    }

    public function processName()
    {
        return $this->belongsTo(ProcessName::class, 'process_names_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('traveler_note_template')
            ->logOnly($this->fillable)->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

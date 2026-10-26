<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualProcessVersion extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['version' => 'integer', 'requires_fig' => 'boolean', 'requires_zone' => 'boolean'];

    public function process() { return $this->belongsTo(Process::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function manualProcess() { return $this->belongsTo(ManualProcess::class)->withTrashed(); }
}

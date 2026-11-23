<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{

    protected $fillable = ['name', 'general_task_id', 'task_has_start_date', 'sort_order'];
    protected $casts = ['sort_order' => 'integer', 'archived_at' => 'datetime'];
    public $timestamps = false;

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeInDisplayOrder(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name')->orderBy('id');
    }

    public function isRequiredByWorkflow(): bool
    {
        return in_array(strtolower(trim($this->name)), ['approved', 'completed', 'post disassembly inspection'], true);
    }

    public function generalTask()
    {
        return $this->belongsTo(GeneralTask::class);
    }

    public function mainRows()
    {
        return $this->hasMany(Main::class);
    }

}

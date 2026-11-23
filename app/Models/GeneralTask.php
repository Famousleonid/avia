<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class GeneralTask extends Model
{
    public const TECHNICIAN_STAGES = ['disassembly', 'assembly'];

    protected $fillable = ['name','sort_order', 'has_start_date'];

    protected $casts = [
        'has_start_date' => 'boolean',
        'catalog_removed_at' => 'datetime',
    ];

    public $timestamps = false;

    public function scopeInCatalog(Builder $query): Builder
    {
        return $query->whereNull('catalog_removed_at');
    }

    public function main()
    {
        return $this->hasMany(Main::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public static function tasksFinished(Collection $tasks, Collection $mainsByTask): bool
    {
        return $tasks->isNotEmpty() && $tasks->every(function (Task $task) use ($mainsByTask): bool {
            $main = $mainsByTask->get($task->id);

            return $main && ($main->ignore_row || $main->date_finish !== null);
        });
    }
}

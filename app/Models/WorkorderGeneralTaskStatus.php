<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkorderGeneralTaskStatus extends Model
{
    protected $fillable = [
        'workorder_id',
        'general_task_id',
        'is_done',
        'done_at',
        'done_user_id',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'done_at' => 'datetime',
    ];

    public function workorder()   { return $this->belongsTo(Workorder::class); }
    public function generalTask() { return $this->belongsTo(GeneralTask::class); }
    public function doneUser()    { return $this->belongsTo(User::class, 'done_user_id'); }
}

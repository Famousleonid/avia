<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Main extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'workorder_id', 'general_task_id', 'description', 'date_start', 'date_finish'];

    protected $casts = [
        'date_start' =>'date:Y-m-d',
        'date_finish' => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workorder()
    {
        return $this->belongsTo(Workorder::class);
    }

    public function generalTask()
    {
        return $this->belongsTo(GeneralTask::class);
    }

}

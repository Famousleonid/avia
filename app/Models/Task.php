<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{

    protected $fillable = ['name','general_task_id','task_has_start_date'];
    public $timestamps = false;


    public function generalTask()
    {
        return $this->belongsTo(GeneralTask::class);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component_main extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'workorder_id', 'task_id', 'description', 'component', 'date_start', 'date_finish'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workorder()
    {
        return $this->belongsTo(Workorder::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /*   public function component()
       {
           return $this->belongsTo(Component::class);
       }*/
}

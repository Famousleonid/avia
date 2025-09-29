<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{

    protected $fillable = ['name'];
    public $timestamps = false;


    public function generalTask() { return $this->belongsTo(GeneralTask::class); }

}

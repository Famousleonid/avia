<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkStage extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'sort_order', 'text_color'];
}

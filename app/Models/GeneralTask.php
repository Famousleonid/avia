<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralTask extends Model
{

    protected $fillable = ['name','sort_order'];

    protected $casts = [
        'has_start_date' => 'boolean',
    ];

    public $timestamps = false;

    public function main()
    {
        return $this->hasMany(Main::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}

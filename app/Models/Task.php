<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{

    protected $fillable = ['name'];
    public $timestamps = false;

    public function component_main()
    {
        return $this->hasMany(Component_main::class);
    }
}

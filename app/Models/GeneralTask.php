<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralTask extends Model
{

    protected $fillable = ['name'];
    public $timestamps = false;

    public function main()
    {
        return $this->hasMany(Main::class);
    }
}

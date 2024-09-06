<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralTask extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function main()
    {
        return $this->hasMany(Main::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    // Confirmed in both local and production team catalogues.
    public const NEVER_STOP_TEAM_ID = 8;

    protected $fillable = ['name'];
    public $timestamps = false;

    public function users()
    {
        return $this->hasMany(User::class);
    }
}

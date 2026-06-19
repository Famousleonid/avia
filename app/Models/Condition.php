<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    use HasFactory;
    public $timestamps = false;

    /** Seeded condition name for parts missing upon arrival. */
    public const NAME_PARTS_MISSING = 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST';

    protected $fillable = [
        'name','unit',
    ];

    public function tdr()
    {
        return $this->hasMany(Tdr::class, 'conditions_id');
    }

    /** The seeded "parts missing upon arrival" condition. */
    public static function partsMissing(): ?self
    {
        return static::where('name', self::NAME_PARTS_MISSING)->first();
    }
}

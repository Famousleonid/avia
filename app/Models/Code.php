<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Code extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** Seeded code name for a missing part. */
    public const NAME_MISSING = 'Missing';

    protected $fillable = [
        'name',
        'code',
        'requires_destruction_cert',
    ];

    protected $casts = [
        'requires_destruction_cert' => 'boolean',
    ];
    public function tdr()
    {
        return $this->hasMany(Tdr::class, 'codes_id');
    }

    /** The seeded "Missing" code. */
    public static function missing(): ?self
    {
        return static::where('name', self::NAME_MISSING)->first();
    }
}

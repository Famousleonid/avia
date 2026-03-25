<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instruction extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['name'];

    public static function overhaulId(): ?int
    {
        static $resolved = false;
        static $id = null;
        if (!$resolved) {
            $id = static::query()->where('name', 'Overhaul')->value('id');
            $resolved = true;
        }

        return $id !== null ? (int) $id : null;
    }

    public function workorder()
    {
        return $this->hasMany(Workorder::class);
    }


}

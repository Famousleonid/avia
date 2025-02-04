<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    use HasFactory;
    protected $fillable = [
        'process_names_id',
        'process',
    ];
    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}

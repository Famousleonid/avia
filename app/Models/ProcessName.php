<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessName extends Model
{
    use HasFactory;
    protected $fillable = [
        'name','process_sheet_name','form_number'
    ];
    public function process()
    {
        return $this->hasMany(Process::class);
    }
}

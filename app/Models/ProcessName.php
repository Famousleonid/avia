<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessName extends Model
{
    use HasFactory;
    protected $fillable = [
        'name','process_sheet_name','form_number','std_days', 'notify_user_id',
    ];
    public $timestamps = false;
    public function processes()
    {
        return $this->hasMany(Process::class, 'process_names_id');
    }

    public function notifyUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'notify_user_id');
    }

}

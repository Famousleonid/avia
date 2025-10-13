<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'manuals_id',
        'date_training', 'form_type',
    ];


    public function manual()
    {
        return $this->belongsTo(Manual::class, 'manuals_id'); // Связь через поле manuals_id
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'manuals_id', 'matrix_row_id',
        'date_training', 'form_type', 'is_legacy',
    ];

    protected $casts = ['is_legacy' => 'boolean'];

    /** Тренинг по SCA-курсу (строке матрицы без CMM); форм 112/132 у таких нет. */
    public function matrixRow()
    {
        return $this->belongsTo(TrainingMatrixRow::class, 'matrix_row_id');
    }


    public function manual()
    {
        return $this->belongsTo(Manual::class, 'manuals_id'); // Связь через поле manuals_id
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

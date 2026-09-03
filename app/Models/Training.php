<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Training extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'user_id', 'manuals_id', 'matrix_row_id',
        'date_training', 'form_type', 'is_legacy',
        'approved_by', 'approved_at',
    ];

    protected $casts = ['is_legacy' => 'boolean', 'approved_at' => 'datetime'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('training')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function isApproved(): bool
    {
        return $this->approved_by !== null;
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

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

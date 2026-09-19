<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualInProcessCheckSheet extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['content' => 'array', 'schema_version' => 'integer'];

    public function manual()
    {
        return $this->belongsTo(Manual::class);
    }
}

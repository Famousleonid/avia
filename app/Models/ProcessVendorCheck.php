<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessVendorCheck extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['checked_at' => 'datetime'];
    public function checkedBy() { return $this->belongsTo(User::class, 'checked_by'); }
}

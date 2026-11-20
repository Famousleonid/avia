<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessVendorRepairOrder extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['document' => 'array', 'cancelled_at' => 'datetime'];
    public function vendor() { return $this->belongsTo(Vendor::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAircraft extends Model
{
    protected $table = 'customer_aircraft';

    protected $fillable = [
        'customer_id',
        'plane_id',
        'quantity',
        'note',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plane(): BelongsTo
    {
        return $this->belongsTo(Plane::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelerProcessTemplateStep extends Model
{
    public $timestamps = false;
    protected $fillable = ['process_id', 'sort_order', 'traveler_group', 'description'];
    protected $casts = ['traveler_group' => 'integer', 'sort_order' => 'integer'];

    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}

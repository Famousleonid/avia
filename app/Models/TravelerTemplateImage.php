<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelerTemplateImage extends Model
{
    use SoftDeletes;

    protected $fillable = ['path', 'name', 'mime', 'sort_order'];
    protected $hidden = ['path', 'deleted_at'];
    protected $appends = ['url'];

    public function template()
    {
        return $this->belongsTo(TravelerProcessTemplate::class, 'template_id');
    }

    public function getUrlAttribute(): string
    {
        return route('traveler-template-images.show', $this);
    }
}

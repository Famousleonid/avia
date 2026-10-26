<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelerProcessTemplate extends Model
{
    protected $fillable = ['manual_id', 'part_number', 'name', 'active', 'revision'];
    protected $casts = ['active' => 'boolean', 'revision' => 'integer'];

    public function images()
    {
        return $this->hasMany(TravelerTemplateImage::class, 'template_id')->orderBy('sort_order')->orderBy('id');
    }

    public function steps()
    {
        return $this->hasMany(TravelerProcessTemplateStep::class, 'template_id')->orderBy('sort_order')->orderBy('id');
    }
}

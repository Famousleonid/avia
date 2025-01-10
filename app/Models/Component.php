<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    use HasFactory;

    protected $fillable = ['part_number', 'name','ipl_num','manual_id'];


    public function manuals()
    {
        return $this->belongsTo(Manual::class,'manual_id');
    }
    public function tdr_component()
    {
        return $this->hasMany(TdrComponent::class);
    }


    /*  public function component_main()
      {
          return $this->hasMany(Component_main::class);
      }*/



}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    use HasFactory;

    protected $fillable = ['purtnumber', 'name'];

    /*  public function component_main()
      {
          return $this->hasMany(Component_main::class);
      }*/
}

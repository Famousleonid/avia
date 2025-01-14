<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
//use Spatie\MediaLibrary\InteractsWithMedia;



class Component extends Model
{
//    use HasFactory;

    protected $fillable = [
        'part_number',
        'assy_part_number',
        'name',
        'ipl_num',
        'assy_ipl_num',
        'manual_id'
    ];


    public function manuals()
    {
        return $this->belongsTo(Manual::class,'manual_id');
    }



    /*  public function component_main()
      {
          return $this->hasMany(Component_main::class);
      }*/


//    public function getThumbnailUrl($collection)
//    {
//        $media = $this->getMedia($collection)->first();
//        return $media
//            ? route('image.show.thumb', ['mediaId' => $media->id, 'modelId' => $this->id, 'mediaName' => $collection])
//            : asset('img/noimage.png');
//    }
//    public function getBigImageUrl($collection)
//    {
//        $media = $this->getMedia($collection)->first();
//        return $media
//            ? route('image.show.big', ['mediaId' => $media->id, 'modelId' => $this->id, 'mediaName' => $collection])
//            : asset('img/noimage.png');
//    }

}

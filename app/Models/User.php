<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements MustVerifyEmail, hasMedia
{
    use HasFactory, Notifiable, InteractsWithMedia;
    use LogsActivity, softDeletes;

    protected $fillable = ['name', 'email', 'password', 'email_verified_at', 'is_admin', 'role_id', 'phone', 'stamp', 'team_id'];
    protected $casts = ['email_verified_at' => 'datetime'];
    protected $hidden = ['password', 'remember_token'];
    protected static $logAttributes = ['name', 'password', 'phone', 'stamp'];
    protected $dates = ['deleted_at'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnly(['name', 'password', 'phone', 'stamp'])
            ->logOnlyDirty();
    }


    public function isAdmin()
    {
        return $this->is_admin == 1;
    }

    public function getRole()
    {
        return $this->role_id;
    }

    public function workorder()
    {
        return $this->hasMany(Workorder::class);
    }
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function log()
    {
        return $this->hasMany(Log::class);
    }

    public function main()
    {
        return $this->hasMany(Main::class);

    }

    public function trainings()
    {
        return $this->hasMany(Training::class);
    }



    public function registerAllMediaConversions(): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->nonOptimized();

    }
    public function getThumbnailUrl($collection)
    {
        $media = $this->getMedia($collection)->first();
        return $media
            ? route('image.show.thumb', ['mediaId' => $media->id, 'modelId' => $this->id, 'mediaName' => 'avatar'])
            : asset('img/noimage.png');
    }

    public function getBigImageUrl($collection)
    {
        $media = $this->getMedia($collection)->first();
        return $media
            ? route('image.show.big', ['mediaId' => $media->id, 'modelId' => $this->id, 'mediaName' => 'avatar'])
            : asset('img/noimage.png');
    }

}

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements MustVerifyEmail, hasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia;
    use LogsActivity;

    protected $fillable = ['name', 'email', 'password', 'email_verified_at', 'is_admin', 'role', 'phone', 'chat', 'stamp', 'team'];
    protected $casts = ['email_verified_at' => 'datetime'];
    protected $hidden = ['password', 'remember_token'];
    protected static $logAttributes = ['name', 'password', 'phone', 'stamp'];


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
        return $this->role;
    }

    public function workorder()
    {
        return $this->hasMany(Workorder::class);
    }

    public function log()
    {
        return $this->hasMany(Log::class);
    }

    public function main()
    {
        return $this->hasMany(Main::class);

    }

    public function registerAllMediaConversions(): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->nonOptimized();

    }


}

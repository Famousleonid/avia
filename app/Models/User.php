<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
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
use App\Models\Manual;


class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasFactory, Notifiable, InteractsWithMedia, HasMediaHelpers, LogsActivity, softDeletes;

    protected $fillable = ['name', 'email', 'password', 'email_verified_at', 'is_admin', 'can_manage_locked_manual_processes', 'can_manage_locked_manual_parts', 'qa_access', 'ec_access', 'role_id', 'phone', 'stamp', 'team_id', 'birthday', 'notification_prefs'];
    protected $casts = ['email_verified_at' => 'datetime', 'notification_prefs' => 'array', 'birthday' => 'date', 'can_manage_locked_manual_processes' => 'boolean', 'can_manage_locked_manual_parts' => 'boolean', 'qa_access' => 'boolean', 'ec_access' => 'boolean'];
    protected $hidden = ['password', 'remember_token'];
    protected static $logAttributes = ['name',  'phone', 'stamp'];
    protected $dates = ['deleted_at'];

    public $mediaUrlName = 'users';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logOnly([
                'name',
                'phone',
                'stamp',
                'role_id',
                'team_id',
                'is_admin',
                'can_manage_locked_manual_processes',
                'can_manage_locked_manual_parts',
                'qa_access',
                'ec_access',
                'email',
                'birthday',
            ])
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty();
    }


    public function isAdmin()
    {
        return $this->is_admin == 1;
    }

    public function isSystemAdmin(): bool
    {
        return $this->isAdmin() && $this->roleIs('Admin');
    }

    public function canManageLockedManualProcesses(): bool
    {
        return $this->isAdmin() || (bool) $this->can_manage_locked_manual_processes;
    }

    public function canManageLockedManualParts(): bool
    {
        return $this->isAdmin() || (bool) $this->can_manage_locked_manual_parts;
    }

    public function roleName(): ?string
    {
        return $this->role?->name;
    }

    public function permittedManuals()
    {
        return $this->belongsToMany(Manual::class, 'manual_user_permissions', 'user_id', 'manual_id')
            ->withTimestamps();
    }

    public function hasFullManualsAccess(): bool
    {
        return (bool) data_get($this->notification_prefs ?? [], 'manuals_full_access', false);
    }

    public function hasQualityAssuranceAccess(): bool
    {
        return (bool) $this->qa_access;
    }

    public function canAccessQualityAssurancePage(): bool
    {
        return $this->isSystemAdmin()
            || ($this->roleIs(['Admin', 'Manager']) && $this->hasQualityAssuranceAccess());
    }

    public function hasEcAccess(): bool
    {
        return (bool) $this->ec_access;
    }

    public function canAccessEcPage(): bool
    {
        return $this->isAdmin() || $this->hasEcAccess();
    }

    public function roleIs(string|array $roles): bool
    {
        $roles = (array) $roles;
        return in_array($this->roleName(), $roles, true);
    }

    public function hasAnyRole(string $pipeSeparated): bool
    {
        $roles = explode('|', $pipeSeparated);
        return $this->roleIs($roles);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function workorder()
    {
        return $this->hasMany(Workorder::class);
    }
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function main()
    {
        return $this->hasMany(Main::class);
    }

    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    public function completedWorkorders()
    {
        return $this->hasMany(Workorder::class, 'done_user_id');
    }
    public function registerAllMediaConversions(): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->nonOptimized();

    }

}

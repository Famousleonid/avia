<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    public const PROJECT_BACKGROUND_COLLECTION = 'project_background';

    use HasFactory, Notifiable, InteractsWithMedia, HasMediaHelpers, LogsActivity, softDeletes;

    // TODO(access): drop legacy access columns after production verifies user_feature_access.
    protected $fillable = ['name', 'selection_name_order', 'email', 'password', 'email_verified_at', 'is_admin', 'can_manage_locked_manual_processes', 'can_manage_locked_manual_parts', 'qa_access', 'ec_access', 'can_sign_certificates', 'role_id', 'phone', 'stamp', 'show_in_training_matrix', 'team_id', 'birthday', 'notification_prefs', 'must_change_password', 'temporary_password_expires_at', 'password_changed_at', 'auth_version'];
    protected $casts = ['email_verified_at' => 'datetime', 'notification_prefs' => 'array', 'birthday' => 'date', 'can_manage_locked_manual_processes' => 'boolean', 'can_manage_locked_manual_parts' => 'boolean', 'qa_access' => 'boolean', 'ec_access' => 'boolean', 'can_sign_certificates' => 'boolean', 'show_in_training_matrix' => 'boolean', 'must_change_password' => 'boolean', 'temporary_password_expires_at' => 'datetime', 'password_changed_at' => 'datetime', 'auth_version' => 'integer'];

    public function hasActiveTemporaryPassword(): bool
    {
        return $this->must_change_password
            && $this->temporary_password_expires_at !== null
            && $this->temporary_password_expires_at->isFuture();
    }

    public function hasExpiredTemporaryPassword(): bool
    {
        return $this->must_change_password
            && $this->temporary_password_expires_at !== null
            && $this->temporary_password_expires_at->isPast();
    }

    public function requiresImmediatePasswordChange(): bool
    {
        return $this->must_change_password
            && ($this->temporary_password_expires_at === null || $this->hasExpiredTemporaryPassword());
    }
    protected $hidden = ['password', 'remember_token', 'selection_name_order'];
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
                'can_sign_certificates',
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
        return $this->isSystemAdmin() || $this->hasExplicitFeatureAccess('manuals.locked_processes');
    }

    public function canManageLockedManualParts(): bool
    {
        return $this->isSystemAdmin() || $this->hasExplicitFeatureAccess('manuals.locked_parts');
    }

    public function roleName(): ?string
    {
        return $this->role?->name;
    }

    public function getSelectionNameAttribute(): string
    {
        $parts = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) < 2 || $this->selection_name_order === 'first_last') {
            return implode(' ', $parts);
        }

        $lastName = array_shift($parts);

        return implode(' ', [...$parts, $lastName]);
    }

    public function scopeWithoutReviewAccounts(Builder $query): Builder
    {
        $emails = collect(config('mobile_review.accounts', []))
            ->keys()
            ->map(fn ($email): string => mb_strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            return $query;
        }

        $emailColumn = $query->getModel()->qualifyColumn('email');
        $placeholders = implode(', ', array_fill(0, count($emails), '?'));

        return $query->where(function (Builder $users) use ($emailColumn, $placeholders, $emails): void {
            $users->whereNull($emailColumn)
                ->orWhereRaw("LOWER({$emailColumn}) NOT IN ({$placeholders})", $emails);
        });
    }

    public function permittedManuals()
    {
        return $this->belongsToMany(Manual::class, 'manual_user_permissions', 'user_id', 'manual_id')
            ->withTimestamps();
    }

    public function hasFullManualsAccess(): bool
    {
        return $this->isSystemAdmin() || $this->hasExplicitFeatureAccess('manuals.full');
    }

    public function hasQualityAssuranceAccess(): bool
    {
        return $this->isSystemAdmin() || $this->hasExplicitFeatureAccess('quality_assurance');
    }

    public function canAccessQualityAssurancePage(): bool
    {
        return $this->hasQualityAssuranceAccess();
    }

    public function hasEcAccess(): bool
    {
        return $this->isSystemAdmin() || $this->hasExplicitFeatureAccess('ec');
    }

    public function canAccessEcPage(): bool
    {
        return $this->hasEcAccess();
    }

    public function canSignCertificates(): bool
    {
        return $this->isSystemAdmin() || $this->hasExplicitFeatureAccess('certificates.sign');
    }

    public function roleIs(string|array $roles): bool
    {
        $roles = (array) $roles;
        return in_array($this->roleName(), $roles, true);
    }

    // ---- Матрица тренингов (Training All) ----

    /** Полный доступ к матрице: все колонки, управление строками/группами/Personnel. */
    public function canManageTrainingMatrix(): bool
    {
        return $this->roleIs('Admin')
            || ($this->roleIs('Manager') && $this->can_sign_certificates);
    }

    /** Видит страницу Training All (хотя бы в урезанном виде). Manager без SCA — нет. */
    public function canViewTrainingMatrix(): bool
    {
        return $this->canManageTrainingMatrix()
            || $this->roleIs('Team Leader')
            || (bool) $this->show_in_training_matrix;
    }

    /** Может добавлять тренинги этому сотруднику (клик по ячейке, формы). */
    public function canManageTrainingsFor(User $target): bool
    {
        if ($this->canManageTrainingMatrix() || $this->id === $target->id) {
            return true;
        }

        return $this->roleIs('Team Leader')
            && $this->team_id !== null
            && (int) $target->team_id === (int) $this->team_id;
    }

    public function hasAnyRole(string $pipeSeparated): bool
    {
        $roles = explode('|', $pipeSeparated);
        return $this->roleIs($roles);
    }

    public function featureAccesses(): HasMany
    {
        return $this->hasMany(UserFeatureAccess::class);
    }

    public function hasExplicitFeatureAccess(string $featureKey): bool
    {
        if ($this->relationLoaded('featureAccesses')) {
            return $this->featureAccesses->contains(
                fn (UserFeatureAccess $access) => $access->feature_key === $featureKey
            );
        }

        return $this->featureAccesses()
            ->where('feature_key', $featureKey)
            ->exists();
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

    public function mobileApiTokens(): HasMany
    {
        return $this->hasMany(MobileApiToken::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PROJECT_BACKGROUND_COLLECTION)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->singleFile();
    }

}

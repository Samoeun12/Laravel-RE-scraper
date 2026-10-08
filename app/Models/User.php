<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'status',
        'theme_preference',
        'avatar',
        'title',
        'phone',
        'last_login_at',
        'last_login_ip',
    ];

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return $this->avatar;
        }
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=6366f1&color=ffffff&bold=true";
    }

    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasRole($roles): bool
    {
        $roleList = is_array($roles) ? $roles : func_get_args();
        $userRoleSlug = strtolower(str_replace(' ', '_', $this->role ?? ''));
        $assignedSlug = $this->roleModel ? strtolower($this->roleModel->slug) : '';

        foreach ($roleList as $r) {
            $check = strtolower(str_replace(' ', '_', $r));
            if ($userRoleSlug === $check || $assignedSlug === $check) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission(string $permissionSlug): bool
    {
        // Administrator always has all permissions
        if ($this->hasRole('admin', 'administrator')) {
            return true;
        }

        if ($this->roleModel) {
            return $this->roleModel->hasPermission($permissionSlug);
        }

        // Fallback role check
        $roleRecord = Role::where('name', $this->role)->orWhere('slug', strtolower(str_replace(' ', '_', $this->role)))->first();
        return $roleRecord ? $roleRecord->hasPermission($permissionSlug) : false;
    }

    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }

    public function getRoleBadgeColorAttribute(): string
    {
        if ($this->roleModel && $this->roleModel->badge_color) {
            return $this->roleModel->badge_color;
        }

        $colors = [
            'administrator' => '#6366f1',
            'manager' => '#10b981',
            'scraper operator' => '#f59e0b',
            'real estate analyst' => '#06b6d4',
            'viewer' => '#64748b',
        ];

        return $colors[strtolower($this->role ?? '')] ?? '#6366f1';
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'created_by');
    }

    public function scraperTasks()
    {
        return $this->hasMany(ScraperTask::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];
}

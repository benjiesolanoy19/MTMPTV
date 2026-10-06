<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Support\RolePermissionMatrix;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'mobile_number',
        'address',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function auditLogs() { return $this->hasMany(AuditLog::class); }
    public function reviewedApplications() { return $this->hasMany(Application::class, 'reviewed_by'); }
    public function recordedViolations() { return $this->hasMany(Violation::class, 'recorded_by'); }
    public function reports() { return $this->hasMany(Report::class, 'submitted_by'); }
    public function notifications() { return $this->hasMany(Notification::class); }
    public function administratorApplications() { return $this->hasMany(AdministratorApplication::class); }
    public function operatorProfile() { return $this->hasOne(Operator::class); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function canManage(string $permission): bool { return $this->hasPermission($permission); }
    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Administrator',
            'viewer' => 'Report User',
            default => ucwords(str_replace('_', ' ', $this->role)),
        };
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->isAdmin()) return true;

        if (!RolePermissionMatrix::allows($this->role, $permission)) {
            return false;
        }

        $legacyAliases = [
            'vehicle owner portal' => ['view dashboard'],
            'vehicle owner vehicles' => ['view vehicles'],
            'vehicle owner applications' => ['view applications', 'create applications'],
            'vehicle owner permits' => ['view permits'],
            'vehicle owner franchises' => ['view franchises'],
            'vehicle owner renewals' => ['view renewals'],
            'vehicle owner violations' => ['view violations'],
            'vehicle owner notifications' => ['view notifications'],
        ];

        $candidatePermissions = array_merge([$permission], $legacyAliases[$permission] ?? []);
        return $this->rolePermissions()->whereIn('permission', $candidatePermissions)->exists();
    }
    public function rolePermissions() { return $this->hasMany(RolePermission::class, 'role', 'role'); }
}

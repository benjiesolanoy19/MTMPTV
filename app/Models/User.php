<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
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
    public function operatorProfile() { return $this->hasOne(Operator::class); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function canManage(): bool { return in_array($this->role, ['admin', 'staff'], true); }
    public function roleLabel(): string { return $this->role === 'viewer' ? 'Report User' : ucwords(str_replace('_', ' ', $this->role)); }

    public function defaultPermissions(): array
    {
        return match ($this->role) {
            'operator' => ['view dashboard', 'operator portal'],
            'vehicle_owner' => ['view dashboard', 'vehicle owner portal', 'vehicle owner vehicles'],
            default => [],
        };
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->isAdmin()) return true;

        $normalized = $permission;
        $aliases = [
            'vehicle owner portal' => ['vehicle owner portal', 'view dashboard'],
            'vehicle owner vehicles' => ['vehicle owner vehicles', 'view vehicles'],
            'vehicle owner applications' => ['vehicle owner applications', 'view applications', 'create applications'],
            'vehicle owner permits' => ['vehicle owner permits', 'view permits'],
            'vehicle owner franchises' => ['vehicle owner franchises', 'view franchises'],
            'vehicle owner renewals' => ['vehicle owner renewals', 'view renewals'],
            'vehicle owner violations' => ['vehicle owner violations', 'view violations'],
            'vehicle owner notifications' => ['vehicle owner notifications', 'view notifications'],
            'view dashboard' => ['view dashboard', 'vehicle owner portal'],
            'view vehicles' => ['view vehicles', 'vehicle owner vehicles'],
            'view applications' => ['view applications', 'vehicle owner applications'],
            'view permits' => ['view permits', 'vehicle owner permits'],
            'view franchises' => ['view franchises', 'vehicle owner franchises'],
            'view renewals' => ['view renewals', 'vehicle owner renewals'],
            'view violations' => ['view violations', 'vehicle owner violations'],
            'view notifications' => ['view notifications', 'vehicle owner notifications'],
        ];

        $candidatePermissions = $aliases[$permission] ?? [$permission];

        if (isset($aliases[$normalized])) {
            $candidatePermissions = $aliases[$normalized];
        }

        if ($this->rolePermissions()->whereIn('permission', $candidatePermissions)->exists()) {
            return true;
        }

        return array_intersect($candidatePermissions, $this->defaultPermissions()) !== [];
    }
    public function rolePermissions() { return $this->hasMany(RolePermission::class, 'role', 'role'); }
}

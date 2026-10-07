<?php

namespace App\Support;

final class RolePermissionMatrix
{
    public const PERMISSIONS = [
        'staff' => [
            'view dashboard',
            'view notifications',
            'view reports',
            'view my reports',
            'staff portal',
            'staff profile',
            'staff onboarding',
            'view vehicles',
            'view operators',
        ],
        'viewer' => [
            'view dashboard',
            'view operators',
            'view vehicles',
            'view franchises',
            'view permits',
            'view renewals',
            'view violations',
            'view reports',
            'view my reports',
            'view notifications',
        ],
        'operator' => [
            'view dashboard',
            'operator portal',
            'operator applications',
            'operator permits',
            'operator franchises',
            'operator renewals',
            'operator violations',
            'operator notifications',
        ],
        'vehicle_owner' => [
            'view dashboard',
            'vehicle owner portal',
            'vehicle owner vehicles',
            'vehicle owner applications',
            'vehicle owner permits',
            'vehicle owner franchises',
            'vehicle owner renewals',
            'vehicle owner violations',
            'vehicle owner notifications',
        ],
    ];

    public const PUBLIC_REGISTRATION_ROLES = ['viewer', 'operator', 'vehicle_owner', 'staff'];

    public static function roles(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    public static function permissionsFor(string $role): array
    {
        return self::PERMISSIONS[$role] ?? [];
    }

    public static function allows(string $role, string $permission): bool
    {
        return in_array($permission, self::permissionsFor($role), true);
    }
}

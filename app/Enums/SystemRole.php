<?php

declare(strict_types=1);

namespace App\Enums;

enum SystemRole: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case USER = 'user';

    /**
     * Protected system roles that cannot be deleted.
     *
     * @return list<string>
     */
    public static function protectedRoles(): array
    {
        return [
            self::SUPERADMIN->value,
            self::ADMIN->value,
            self::MODERATOR->value,
            self::USER->value,
        ];
    }

    /**
     * Check if a given role name is a protected system role.
     */
    public static function isSystemRole(string $roleName): bool
    {
        return in_array($roleName, self::protectedRoles(), true);
    }

    /**
     * All system roles as strings.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}

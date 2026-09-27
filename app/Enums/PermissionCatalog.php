<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionCatalog: string
{
    case DASHBOARD_VIEW = 'dashboard.view';
    case USERS_VIEW = 'users.view';
    case USERS_UPDATE = 'users.update';
    case USERS_SUSPEND = 'users.suspend';
    case USERS_DELETE = 'users.delete';
    case USERS_ASSIGN_ROLES = 'users.assign-roles';
    case ROLES_VIEW = 'roles.view';
    case ROLES_CREATE = 'roles.create';
    case ROLES_UPDATE = 'roles.update';
    case ROLES_DELETE = 'roles.delete';
    case ROLES_ASSIGN_PERMISSIONS = 'roles.assign-permissions';
    case COMMENTS_VIEW = 'comments.view';
    case COMMENTS_MODERATE = 'comments.moderate';
    case COMMENTS_DELETE = 'comments.delete';
    case REPORTS_VIEW = 'reports.view';
    case REPORTS_RESOLVE = 'reports.resolve';
    case ANALYTICS_VIEW = 'analytics.view';
    case CACHE_MANAGE = 'cache.manage';
    case SETTINGS_VIEW = 'settings.view';
    case SETTINGS_MANAGE = 'settings.manage';
    case AUDIT_LOGS_VIEW = 'audit-logs.view';

    /**
     * All permissions as an array of strings.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Default permissions granted to the admin role.
     *
     * @return list<string>
     */
    public static function adminDefault(): array
    {
        return [
            self::DASHBOARD_VIEW->value,
            self::USERS_VIEW->value,
            self::COMMENTS_VIEW->value,
            self::COMMENTS_MODERATE->value,
            self::REPORTS_RESOLVE->value,
            self::ANALYTICS_VIEW->value,
        ];
    }

    /**
     * Default permissions granted to the moderator role.
     *
     * @return list<string>
     */
    public static function moderatorDefault(): array
    {
        return [
            self::DASHBOARD_VIEW->value,
            self::USERS_VIEW->value,
            self::COMMENTS_VIEW->value,
            self::COMMENTS_MODERATE->value,
            self::REPORTS_RESOLVE->value,
        ];
    }

    /**
     * Default permissions granted to the user role.
     *
     * @return list<string>
     */
    public static function userDefault(): array
    {
        return [
            self::COMMENTS_VIEW->value,
        ];
    }
}

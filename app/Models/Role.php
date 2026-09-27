<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SystemRole;
use DomainException;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (Role $role): void {
            if (SystemRole::isSystemRole($role->name)) {
                throw new DomainException("Role sistem '{$role->name}' dilindungi dan tidak dapat dihapus.");
            }
        });
    }

    /**
     * Check if this role is a protected system role.
     */
    public function isSystemRole(): bool
    {
        return SystemRole::isSystemRole($this->name);
    }
}

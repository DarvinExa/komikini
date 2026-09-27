<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SystemRole;
use Database\Factories\UserFactory;
use DomainException;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'name',
        'username',
        'email',
        'password',
        'avatar_path',
        'status',
        'suspended_until',
        'email_verified_at',
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
     * Activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'username', 'email', 'status', 'suspended_until'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    /**
     * Boot model events.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->public_id)) {
                $user->public_id = (string) Str::ulid();
            }
        });

        static::deleting(function (User $user): void {
            if ($user->hasRole(SystemRole::SUPERADMIN->value)) {
                $activeCount = static::role(SystemRole::SUPERADMIN->value)
                    ->where('status', 'active')
                    ->where(function ($query): void {
                        $query->whereNull('suspended_until')
                            ->orWhere('suspended_until', '<=', now());
                    })
                    ->where('id', '!=', $user->id)
                    ->count();

                if ($activeCount === 0) {
                    throw new DomainException('Satu-satunya superadmin aktif tidak dapat dihapus.');
                }
            }
        });
    }

    /**
     * Check if the user account is suspended.
     */
    public function isSuspended(): bool
    {
        if ($this->status === 'suspended') {
            return true;
        }

        if ($this->suspended_until !== null && $this->suspended_until->isFuture()) {
            return true;
        }

        return false;
    }

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
            'suspended_until' => 'datetime',
        ];
    }
}

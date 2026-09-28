<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ComicProviderInterface;
use App\Enums\SystemRole;
use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\ReadingHistory;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Policies\BookmarkPolicy;
use App\Policies\CommentPolicy;
use App\Policies\CommentReportPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ReadingHistoryPolicy;
use App\Policies\RolePolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\UserPolicy;
use App\Services\Comic\CachedComicProvider;
use App\Services\Comic\KomikuProvider;
use App\Services\Comic\KomikuResponseMapper;
use App\Services\RbacService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RbacService::class);

        $this->app->singleton(ComicProviderInterface::class, function ($app) {
            $baseProvider = new KomikuProvider(
                mapper: $app->make(KomikuResponseMapper::class),
            );

            return new CachedComicProvider(
                provider: $baseProvider,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate Limiters
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinutes(15, 3)->by($email.$request->ip());
        });

        RateLimiter::for('verification-notification', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('progress', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('comment-create', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('comment-report', function (Request $request) {
            return Limit::perMinutes(60, 5)->by($request->user()?->id ?: $request->ip());
        });

        // Register Policies
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(CommentReport::class, CommentReportPolicy::class);
        Gate::policy(SystemSetting::class, SystemSettingPolicy::class);
        Gate::policy(ReadingHistory::class, ReadingHistoryPolicy::class);
        Gate::policy(Bookmark::class, BookmarkPolicy::class);

        // Superadmin Gate::before with invariant protections
        Gate::before(function ($user, string $ability, array $arguments = []): ?bool {
            // Protected Invariant: System roles cannot be deleted
            if ($ability === 'delete' && isset($arguments[0]) && $arguments[0] instanceof Role && $arguments[0]->isSystemRole()) {
                return false;
            }

            // Protected Invariant: Sole active superadmin cannot be deleted or suspended
            if (in_array($ability, ['delete', 'suspend'], true) && isset($arguments[0]) && $arguments[0] instanceof User) {
                if (app(RbacService::class)->isLastActiveSuperadmin($arguments[0])) {
                    return false;
                }
            }

            // Superadmin obtains all other abilities
            if ($user->hasRole(SystemRole::SUPERADMIN->value)) {
                return true;
            }

            return null;
        });
    }
}

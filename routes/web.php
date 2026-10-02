<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminModerationController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ChapterReaderController;
use App\Http\Controllers\ComicBrowseController;
use App\Http\Controllers\ComicDetailController;
use App\Http\Controllers\ComicRankingController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReadingProgressController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Operations & Health Endpoints
Route::get('/health/ready', [HealthController::class, 'ready'])->name('health.ready');
Route::get('/health/upstream', [HealthController::class, 'upstream'])->name('health.upstream');

// SEO & Crawler Endpoints
Route::get('/robots.txt', RobotsController::class)->name('seo.robots');
Route::get('/sitemap.xml', SitemapController::class)->name('seo.sitemap');

// Image Proxy Endpoint (Bypasses ISP DNS64 local ULA synthesis & Chrome PNA CORS blocks)
Route::get('/img-proxy', ImageProxyController::class)->name('image.proxy');

// Public Discovery, Reader, Ranking & Comment Routes
Route::get('/', HomeController::class)->name('home');
Route::get('/terbaru', [ComicBrowseController::class, 'latest'])->name('comics.latest');
Route::get('/ranking', [ComicRankingController::class, 'index'])->name('comics.ranking');
Route::get('/type/{type}', [ComicBrowseController::class, 'byType'])->name('comics.type');
Route::get('/genre', [ComicBrowseController::class, 'genres'])->name('comics.genres');
Route::get('/genre/{slug}', [ComicBrowseController::class, 'byGenre'])->name('comics.genre');
Route::get('/komik/{slug}', [ComicDetailController::class, 'show'])->name('comics.detail');
Route::get('/komik/{slug}/{chapter}', [ChapterReaderController::class, 'show'])->name('comics.chapter');
Route::redirect('/comic/{slug}', '/komik/{slug}', 301);
Route::redirect('/comic/{slug}/chapter/{chapter}', '/komik/{slug}/{chapter}', 301);
Route::get('/search', SearchController::class)->middleware('throttle:search')->name('comics.search');
Route::get('/comments', [CommentController::class, 'index'])->name('comments.index');

// Authenticated User Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::post('/profile/logout-other-sessions', [ProfileController::class, 'logoutOtherSessions'])->name('profile.logout-other-sessions');

    // Library, Bookmarks & Reading History
    Route::get('/pustaka', [LibraryController::class, 'index'])->name('library.index');
    Route::delete('/pustaka/riwayat/{history}', [LibraryController::class, 'destroyHistory'])->name('library.history.destroy');
    Route::delete('/pustaka/riwayat', [LibraryController::class, 'clearAllHistory'])->name('library.history.clear');
    Route::delete('/pustaka/bookmark/{bookmark}', [LibraryController::class, 'destroyBookmark'])->name('library.bookmark.destroy');
    Route::post('/komik/{slug}/bookmark', [BookmarkController::class, 'toggle'])->name('comics.bookmark.toggle');
    Route::post('/library/progress', [ReadingProgressController::class, 'store'])->middleware('throttle:progress')->name('library.progress');

    // Community Comments, Replies, Reactions & Moderation
    Route::post('/comments', [CommentController::class, 'store'])->middleware('throttle:comment-create')->name('comments.store');
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/like', [CommentController::class, 'like'])->name('comments.like');
    Route::post('/comments/{comment}/report', [CommentController::class, 'report'])->middleware('throttle:comment-report')->name('comments.report');
    Route::post('/comments/{comment}/moderate', [CommentController::class, 'moderate'])->name('comments.moderate');

    // Administration Console Routes
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('/cache/flush', [AdminDashboardController::class, 'flushCache'])->middleware('throttle:admin-mutation')->name('admin.cache.flush');

        // User Management
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->middleware('throttle:admin-mutation')->name('admin.users.suspend');
        Route::post('/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->middleware('throttle:admin-mutation')->name('admin.users.unsuspend');
        Route::put('/users/{user}/roles', [AdminUserController::class, 'assignRoles'])->middleware('throttle:admin-mutation')->name('admin.users.roles');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->middleware('throttle:admin-mutation')->name('admin.users.destroy');

        // Roles & Permissions Matrix
        Route::get('/roles', [AdminRoleController::class, 'index'])->name('admin.roles.index');
        Route::post('/roles', [AdminRoleController::class, 'store'])->middleware('throttle:admin-mutation')->name('admin.roles.store');
        Route::put('/roles/{role}/permissions', [AdminRoleController::class, 'updatePermissions'])->middleware('throttle:admin-mutation')->name('admin.roles.permissions');
        Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->middleware('throttle:admin-mutation')->name('admin.roles.destroy');

        // Moderation Queue & Comments
        Route::get('/moderation', [AdminModerationController::class, 'index'])->name('admin.moderation.index');
        Route::post('/reports/{report}/resolve', [AdminModerationController::class, 'resolveReport'])->middleware('throttle:admin-mutation')->name('admin.reports.resolve');
        Route::post('/comments/bulk-moderate', [AdminModerationController::class, 'bulkModerateComments'])->middleware('throttle:admin-mutation')->name('admin.comments.bulk-moderate');

        // Audit Logs
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('admin.audit-logs.index');
    });

    // Route policy check test endpoint for verified users
    Route::get('/verified-only', function () {
        return response()->json(['message' => 'verified-content']);
    })->middleware('verified')->name('verified.test');
});

require __DIR__.'/auth.php';

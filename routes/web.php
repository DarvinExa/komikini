<?php

declare(strict_types=1);

use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ChapterReaderController;
use App\Http\Controllers\ComicBrowseController;
use App\Http\Controllers\ComicDetailController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReadingProgressController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Public Discovery, Reader & Comment Routes
Route::get('/', HomeController::class)->name('home');
Route::get('/terbaru', [ComicBrowseController::class, 'latest'])->name('comics.latest');
Route::get('/type/{type}', [ComicBrowseController::class, 'byType'])->name('comics.type');
Route::get('/genre', [ComicBrowseController::class, 'genres'])->name('comics.genres');
Route::get('/genre/{slug}', [ComicBrowseController::class, 'byGenre'])->name('comics.genre');
Route::get('/komik/{slug}', [ComicDetailController::class, 'show'])->name('comics.detail');
Route::get('/komik/{slug}/{chapter}', [ChapterReaderController::class, 'show'])->name('comics.chapter');
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

    // Route policy check test endpoint for verified users
    Route::get('/verified-only', function () {
        return response()->json(['message' => 'verified-content']);
    })->middleware('verified')->name('verified.test');
});

require __DIR__.'/auth.php';

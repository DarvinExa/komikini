<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler for Comic Ranking Aggregation & Raw View Retention
Schedule::command('komik:aggregate-rankings')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('komik:prune-view-events')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

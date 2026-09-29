<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ranking\ViewEventRetentionService;
use Illuminate\Console\Command;

class PruneViewEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'komik:prune-view-events {--days= : Jumlah hari retensi (default dari config, biasanya 90)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan raw view event yang melebihi batas retensi tanpa menghapus agregat all-time';

    /**
     * Execute the console command.
     */
    public function handle(ViewEventRetentionService $service): int
    {
        $days = $this->option('days') ? (int) $this->option('days') : null;

        $this->info('Memulai pembersihan raw view event yang kedaluwarsa...');
        $prunedCount = $service->prune($days);
        $this->info("Berhasil membersihkan {$prunedCount} raw view event (agregat historis telah diamankan ke baseline).");

        return self::SUCCESS;
    }
}

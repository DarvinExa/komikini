<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RankingPeriod;
use App\Services\Ranking\RankingAggregationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AggregateRankingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'komik:aggregate-rankings {--period= : Tentukan periode agregasi spesifik (daily, weekly, monthly, all_time)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agregasi data view event ke tabel peringkat popularitas internal';

    /**
     * Execute the console command.
     */
    public function handle(RankingAggregationService $service): int
    {
        $lock = Cache::lock('command:aggregate_rankings', 120);

        if (! $lock->get()) {
            $this->warn('Perintah dibatalkan: proses agregasi ranking lain sedang berjalan.');

            return self::SUCCESS;
        }

        try {
            $periodOption = $this->option('period');

            if ($periodOption) {
                $period = RankingPeriod::tryFrom((string) $periodOption);
                if (! $period) {
                    $this->error("Periode tidak valid: {$periodOption}. Pilihan yang tersedia: ".implode(', ', RankingPeriod::values()));

                    return self::FAILURE;
                }

                $count = $service->aggregatePeriod($period);
                $this->info("Berhasil mengagregasi ranking periode [{$period->value}]: {$count} komik.");
            } else {
                $this->info('Memulai agregasi seluruh periode ranking...');
                $results = $service->aggregateAll();

                foreach ($results as $p => $c) {
                    $this->line(" - [{$p}]: {$c} komik");
                }

                $this->info('Agregasi seluruh ranking selesai.');
            }

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}

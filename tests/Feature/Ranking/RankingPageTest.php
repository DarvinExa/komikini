<?php

declare(strict_types=1);

namespace Tests\Feature\Ranking;

use App\Enums\RankingPeriod;
use App\Models\Comic;
use App\Models\ComicRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RankingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_page_renders_with_inertia_component(): void
    {
        $response = $this->get(route('comics.ranking'));

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Comic/Ranking')
                ->has('activePeriod')
                ->has('periods')
                ->has('items')
                ->where('activePeriod', 'daily')
            );
    }

    public function test_ranking_page_shows_empty_state_honestly(): void
    {
        $response = $this->get(route('comics.ranking', ['period' => 'weekly']));

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Comic/Ranking')
                ->where('activePeriod', 'weekly')
                ->where('items', [])
                ->where('calculatedAt', null)
            );
    }

    public function test_ranking_page_displays_ranked_items_correctly(): void
    {
        $comic = Comic::create([
            'slug' => 'solo-leveling',
            'title' => 'Solo Leveling',
            'comic_type' => 'manhwa',
        ]);

        ComicRanking::create([
            'comic_id' => $comic->id,
            'period' => RankingPeriod::DAILY->value,
            'qualified_views' => 1500,
            'unique_readers' => 450,
            'rank_position' => 1,
            'calculated_at' => now(),
        ]);

        $response = $this->get(route('comics.ranking', ['period' => 'daily']));

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Comic/Ranking')
                ->has('items', 1)
                ->where('items.0.rank_position', 1)
                ->where('items.0.qualified_views', 1500)
                ->where('items.0.unique_readers', 450)
                ->where('items.0.comic.title', 'Solo Leveling')
            );
    }

    public function test_invalid_period_query_param_falls_back_to_daily(): void
    {
        $response = $this->get(route('comics.ranking', ['period' => 'invalid_random_string']));

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Comic/Ranking')
                ->where('activePeriod', 'daily')
            );
    }
}

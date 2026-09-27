<?php

declare(strict_types=1);

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_home_page_renders_via_inertia(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Correlation-ID');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('appName')
            ->has('correlationId')
            ->where('appName', config('app.name'))
            ->has('recommended')
            ->has('popular')
            ->has('latest')
            ->has('genres')
        );
    }
}

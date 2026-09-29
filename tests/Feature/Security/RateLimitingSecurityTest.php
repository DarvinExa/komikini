<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Services\Security\TurnstileService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RateLimitingSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_login_throttles_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'throttletest@example.com',
                'password' => 'WrongPass!',
            ]);
        }

        // 6th attempt should be throttled (429)
        $response = $this->post('/login', [
            'email' => 'throttletest@example.com',
            'password' => 'WrongPass!',
        ]);

        $response->assertStatus(429);
    }

    public function test_register_throttles_after_three_attempts(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/register', [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'ValidPassword123!',
                'password_confirmation' => 'ValidPassword123!',
            ]);
        }

        // 4th attempt should be throttled (429)
        $response = $this->post('/register', [
            'name' => 'User 4',
            'email' => 'user4@example.com',
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!',
        ]);

        $response->assertStatus(429);
    }

    public function test_password_reset_request_throttles_after_three_attempts(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', [
                'email' => 'reset_limit@example.com',
            ]);
        }

        // 4th attempt should be throttled
        $response = $this->post('/forgot-password', [
            'email' => 'reset_limit@example.com',
        ]);

        $response->assertStatus(429);
    }

    public function test_turnstile_service_adaptive_behavior(): void
    {
        // 1. When secret key is empty, service passes adaptively
        $unconfiguredService = new TurnstileService(secretKey: '');
        $this->assertFalse($unconfiguredService->isEnabled());
        $this->assertTrue($unconfiguredService->verify('any_token'));
        $this->assertTrue($unconfiguredService->verify(null));

        // 2. When secret key is configured, service verifies against Cloudflare
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::sequence()
                ->push(['success' => true], 200)
                ->push(['success' => false, 'error-codes' => ['invalid-input-response']], 200),
        ]);

        $configuredService = new TurnstileService(secretKey: 'mock-secret-key-12345');
        $this->assertTrue($configuredService->isEnabled());
        $this->assertTrue($configuredService->verify('valid-cf-token', '127.0.0.1'));

        // 3. Fails when Cloudflare returns success: false (second sequenced response)
        $this->assertFalse($configuredService->verify('invalid-cf-token', '127.0.0.1'));

        // 4. Empty token fails when configured (no HTTP call needed)
        $this->assertFalse($configuredService->verify(null));
        $this->assertFalse($configuredService->verify(''));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSuperadminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_can_create_new_superadmin_via_command(): void
    {
        $this->artisan('komikini:create-superadmin', [
            '--name' => 'Admin Utama',
            '--email' => 'super@komikini.test',
            '--password' => 'SecurePass123!@#',
        ])
            ->expectsOutputToContain('Superadmin [super@komikini.test] berhasil dibuat.')
            ->assertSuccessful();

        $user = User::where('email', 'super@komikini.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(SystemRole::SUPERADMIN->value));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_can_promote_existing_user_to_superadmin_via_command(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@komikini.test',
        ]);

        $this->artisan('komikini:create-superadmin', [
            '--name' => $existing->name,
            '--email' => 'existing@komikini.test',
        ])
            ->expectsOutputToContain('Role superadmin berhasil diberikan kepada user terdaftar: existing@komikini.test')
            ->assertSuccessful();

        $this->assertTrue($existing->fresh()->hasRole(SystemRole::SUPERADMIN->value));
    }
}

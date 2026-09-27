<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Enums\SystemRole;
use App\Models\Activity;
use App\Models\User;
use App\Services\RbacService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected RbacService $rbacService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->rbacService = app(RbacService::class);
    }

    public function test_role_assignment_is_logged_in_audit_log(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $targetUser = User::factory()->create();

        $this->rbacService->assignRoles($superadmin, $targetUser, [SystemRole::MODERATOR->value]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'rbac',
            'description' => 'user.roles_updated',
            'causer_id' => $superadmin->id,
            'subject_id' => $targetUser->id,
        ]);

        $activity = Activity::where('description', 'user.roles_updated')->latest()->first();
        $this->assertNotNull($activity);
        $this->assertContains(SystemRole::MODERATOR->value, $activity->properties['new_roles']);
    }

    public function test_audit_log_never_logs_passwords_or_secrets(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        // Update user properties
        $user->update([
            'name' => 'Updated Name',
            'password' => 'newSecret456',
        ]);

        $activity = Activity::where('subject_id', $user->id)
            ->where('log_name', 'user')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayNotHasKey('password', $activity->properties['attributes'] ?? []);

        // Also test custom activity creation with sensitive keys
        activity()
            ->withProperties([
                'safe_key' => 'safe_value',
                'password' => 'plaintext_secret',
                'token' => 'super_secret_token',
            ])
            ->log('test.sensitive_check');

        $testActivity = Activity::where('description', 'test.sensitive_check')->first();
        $this->assertSame('safe_value', $testActivity->properties['safe_key']);
        $this->assertSame('[REDACTED]', $testActivity->properties['password']);
        $this->assertSame('[REDACTED]', $testActivity->properties['token']);
    }

    public function test_audit_log_captures_correlation_id_and_ip_during_http_request(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'X-Correlation-ID' => 'test-audit-corr-id-12345',
        ])->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Name With Correlation',
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();

        $activity = Activity::where('subject_id', $user->id)
            ->where('log_name', 'user')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('test-audit-corr-id-12345', $activity->properties['correlation_id'] ?? null);
    }
}

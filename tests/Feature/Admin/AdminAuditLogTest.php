<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_guest_cannot_view_audit_logs(): void
    {
        $response = $this->get('/admin/audit-logs');
        $response->assertRedirect('/login');
    }

    public function test_user_without_audit_logs_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::USER->value);

        $response = $this->actingAs($user)->get('/admin/audit-logs');
        $response->assertForbidden();
    }

    public function test_superadmin_can_view_audit_logs_and_filter(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        activity('security')
            ->causedBy($superadmin)
            ->withProperties(['ip' => '127.0.0.1'])
            ->log('admin.login_verified');

        activity('general')
            ->log('system.cron_completed');

        $response = $this->actingAs($superadmin)->get('/admin/audit-logs?log_name=security');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/AuditLogs/Index')
            ->has('logs.data', 1)
            ->where('logs.data.0.log_name', 'security')
            ->where('logs.data.0.description', 'admin.login_verified')
            ->has('availableLogNames')
        );
    }
}

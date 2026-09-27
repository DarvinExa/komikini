<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Enums\SystemRole;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PolicySkeletonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_comment_policy_author_and_moderator_paths(): void
    {
        $author = User::factory()->create();
        $author->assignRole(SystemRole::USER->value);

        $otherUser = User::factory()->create();
        $otherUser->assignRole(SystemRole::USER->value);

        $moderator = User::factory()->create();
        $moderator->assignRole(SystemRole::MODERATOR->value);

        $comment = new Comment;
        $comment->id = 1;
        $comment->user_id = $author->id;
        $comment->status = 'published';

        // Author can update own comment
        $this->assertTrue(Gate::forUser($author)->allows('update', $comment));

        // Other user cannot update comment
        $this->assertFalse(Gate::forUser($otherUser)->allows('update', $comment));

        // Moderator can moderate comment, but normal user cannot
        $this->assertTrue(Gate::forUser($moderator)->allows('moderate', $comment));
        $this->assertFalse(Gate::forUser($otherUser)->allows('moderate', $comment));
    }

    public function test_comment_policy_denies_suspended_users(): void
    {
        $suspendedUser = User::factory()->suspended()->create();
        $suspendedUser->assignRole(SystemRole::USER->value);

        $this->assertFalse(Gate::forUser($suspendedUser)->allows('create', Comment::class));
    }

    public function test_comment_report_policy_paths(): void
    {
        $reporter = User::factory()->create();
        $reporter->assignRole(SystemRole::USER->value);

        $moderator = User::factory()->create();
        $moderator->assignRole(SystemRole::MODERATOR->value);

        $otherUser = User::factory()->create();
        $otherUser->assignRole(SystemRole::USER->value);

        $report = new CommentReport;
        $report->id = 1;
        $report->reporter_id = $reporter->id;
        $report->status = 'open';

        // Reporter can view own report
        $this->assertTrue(Gate::forUser($reporter)->allows('view', $report));

        // Other user cannot view report
        $this->assertFalse(Gate::forUser($otherUser)->allows('view', $report));

        // Moderator can resolve report
        $this->assertTrue(Gate::forUser($moderator)->allows('resolve', $report));
        $this->assertFalse(Gate::forUser($otherUser)->allows('resolve', $report));
    }

    public function test_system_setting_policy_paths(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $user = User::factory()->create();
        $user->assignRole(SystemRole::USER->value);

        $setting = new SystemSetting;
        $setting->id = 1;
        $setting->key = 'site.name';

        $this->assertTrue(Gate::forUser($superadmin)->allows('manage', $setting));
        $this->assertFalse(Gate::forUser($user)->allows('manage', $setting));
    }

    public function test_permission_policy_paths(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $user = User::factory()->create();
        $user->assignRole(SystemRole::USER->value);

        $permission = Permission::first();

        $this->assertTrue(Gate::forUser($superadmin)->allows('view', $permission));
        $this->assertFalse(Gate::forUser($user)->allows('view', $permission));
    }
}

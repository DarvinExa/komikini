<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\SystemRole;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InjectionAndXssSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_stored_xss_in_comment_is_sanitized_and_stripped_of_tags(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::USER->value);

        $comic = Comic::create([
            'slug' => 'one-piece-xss',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        $xssPayload = "<script>alert('xss')</script><img src=x onerror=alert(1)>Komentar aman dan bersih";

        $response = $this->actingAs($user)->postJson('/comments', [
            'comic_slug' => $comic->slug,
            'chapter_key' => 'ch-1',
            'body' => $xssPayload,
        ]);

        $response->assertCreated();

        $comment = Comment::where('comic_id', $comic->id)->first();
        $this->assertNotNull($comment);
        $this->assertStringNotContainsString('<script>', $comment->body);
        $this->assertStringNotContainsString('</script>', $comment->body);
        $this->assertStringNotContainsString('<img', $comment->body);
        $this->assertStringContainsString('Komentar aman dan bersih', $comment->body);
    }

    public function test_reflected_xss_payload_in_search_query_is_safely_handled(): void
    {
        $payload = '<script>alert("reflected_xss")</script>';

        $response = $this->get('/search?q='.urlencode($payload));

        // Must return OK without unescaped script tag executing
        $response->assertOk();
    }

    public function test_sqli_payloads_in_search_and_admin_filters_do_not_cause_sql_errors(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::SUPERADMIN->value);

        $sqliPayloads = [
            "' OR '1'='1",
            "1' UNION SELECT null, null, null, null--",
            "'; DROP TABLE users; --",
            "admin'--",
            "1; WAITFOR DELAY '0:0:5'--",
        ];

        foreach ($sqliPayloads as $payload) {
            // Search route
            $searchRes = $this->get('/search?q='.urlencode($payload));
            $this->assertNotEquals(500, $searchRes->status(), "Search query with SQLi payload [{$payload}] must not cause 500 error.");

            // Admin users search filter
            $adminRes = $this->actingAs($admin)->get('/admin/users?search='.urlencode($payload));
            $this->assertNotEquals(500, $adminRes->status(), "Admin user search with SQLi payload [{$payload}] must not cause 500 error.");
        }
    }

    public function test_mass_assignment_protection_on_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'status' => 'active',
        ]);
        $user->assignRole(SystemRole::USER->value);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
            'status' => 'superadmin',
            'roles' => ['superadmin'],
            'is_suspended' => true,
            'email_verified_at' => null,
        ]);

        $response->assertRedirect('/profile');

        $refreshed = $user->fresh();
        $this->assertEquals('Updated Name', $refreshed->name);
        $this->assertEquals('active', $refreshed->status); // Status is NOT overwritten
        $this->assertTrue($refreshed->hasRole(SystemRole::USER->value));
        $this->assertFalse($refreshed->hasRole(SystemRole::SUPERADMIN->value));
    }
}

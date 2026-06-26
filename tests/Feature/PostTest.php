<?php

namespace Tests\Feature;

use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_index_returns_posts(): void
    {
        $rawContents = RawContent::factory()->count(3)->create(['user_id' => $this->user->id]);
        foreach ($rawContents as $rc) {
            GeneratedPost::factory()->create(['raw_content_id' => $rc->id]);
        }

        $response = $this->getJson('/api/posts', $this->headers());

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_only_returns_owned_posts(): void
    {
        $rc = RawContent::factory()->create(['user_id' => $this->user->id]);
        GeneratedPost::factory()->create(['raw_content_id' => $rc->id]);
        GeneratedPost::factory()->create();

        $response = $this->getJson('/api/posts', $this->headers());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_status(): void
    {
        $rc1 = RawContent::factory()->create(['user_id' => $this->user->id]);
        $rc2 = RawContent::factory()->create(['user_id' => $this->user->id]);
        GeneratedPost::factory()->create(['raw_content_id' => $rc1->id, 'statut' => 'draft']);
        GeneratedPost::factory()->create(['raw_content_id' => $rc2->id, 'statut' => 'posted']);

        $response = $this->getJson('/api/posts?statut=posted', $this->headers());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('posted', $response->json('data.0.statut'));
    }

    public function test_show_returns_post(): void
    {
        $rc = RawContent::factory()->create(['user_id' => $this->user->id]);
        $post = GeneratedPost::factory()->create(['raw_content_id' => $rc->id]);

        $response = $this->getJson("/api/posts/{$post->id}", $this->headers());

        $response->assertStatus(200);
        $this->assertEquals($post->id, $response->json('data.id'));
        $this->assertEquals($post->hook_propose, $response->json('data.hook_propose'));
    }

    public function test_show_returns_404_for_other_users_post(): void
    {
        $post = GeneratedPost::factory()->create();

        $response = $this->getJson("/api/posts/{$post->id}", $this->headers());

        $response->assertStatus(404);
    }

    public function test_update_status_changes_post_status(): void
    {
        $rc = RawContent::factory()->create(['user_id' => $this->user->id]);
        $post = GeneratedPost::factory()->create([
            'raw_content_id' => $rc->id,
            'statut' => 'draft',
        ]);

        $response = $this->patchJson("/api/posts/{$post->id}/status", [
            'statut' => 'posted',
        ], $this->headers());

        $response->assertStatus(200);
        $this->assertEquals('posted', $response->json('post.statut'));

        $this->assertDatabaseHas('generated_posts', [
            'id' => $post->id,
            'statut' => 'posted',
        ]);
    }

    public function test_update_status_validates_allowed_values(): void
    {
        $rc = RawContent::factory()->create(['user_id' => $this->user->id]);
        $post = GeneratedPost::factory()->create(['raw_content_id' => $rc->id]);

        $response = $this->patchJson("/api/posts/{$post->id}/status", [
            'statut' => 'invalid',
        ], $this->headers());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['statut']);
    }

    public function test_update_status_returns_404_for_other_users_post(): void
    {
        $post = GeneratedPost::factory()->create();

        $response = $this->patchJson("/api/posts/{$post->id}/status", [
            'statut' => 'posted',
        ], $this->headers());

        $response->assertStatus(404);
    }

    public function test_json_columns_are_native_arrays(): void
    {
        $rc = RawContent::factory()->create(['user_id' => $this->user->id]);
        $post = GeneratedPost::factory()->create([
            'raw_content_id' => $rc->id,
            'body_points' => ['Point 1', 'Point 2'],
            'suggested_hashtags' => ['#Tech', '#Laravel'],
        ]);

        $response = $this->getJson("/api/posts/{$post->id}", $this->headers());

        $response->assertStatus(200);

        $bodyPoints = $response->json('data.body_points');
        $this->assertIsArray($bodyPoints);
        $this->assertEquals(['Point 1', 'Point 2'], $bodyPoints);

        $hashtags = $response->json('data.suggested_hashtags');
        $this->assertIsArray($hashtags);
        $this->assertEquals(['#Tech', '#Laravel'], $hashtags);
    }
}

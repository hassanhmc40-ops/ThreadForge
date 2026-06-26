<?php

namespace Tests\Feature;

use App\Models\Blueprint;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlueprintTest extends TestCase
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

    public function test_index_returns_blueprints(): void
    {
        Blueprint::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/blueprints', $this->headers());

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_only_returns_owned_blueprints(): void
    {
        Blueprint::factory()->create(['user_id' => $this->user->id]);
        Blueprint::factory()->create();

        $response = $this->getJson('/api/blueprints', $this->headers());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_store_creates_blueprint(): void
    {
        $response = $this->postJson('/api/blueprints', [
            'name' => 'Tech Twitter Style',
            'tone' => 'professional yet relaxed',
            'max_hashtags' => 3,
            'max_characters' => 280,
            'regles_supplementaires' => '["Keep it concise","Use code examples"]',
        ], $this->headers());

        $response->assertStatus(201);
        $this->assertEquals('Tech Twitter Style', $response->json('data.name'));
        $this->assertEquals(3, $response->json('data.max_hashtags'));
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/blueprints', [], $this->headers());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'tone']);
    }

    public function test_store_uses_defaults(): void
    {
        $response = $this->postJson('/api/blueprints', [
            'name' => 'Test',
            'tone' => 'Casual',
        ], $this->headers());

        $response->assertStatus(201);
        $this->assertEquals(1, $response->json('data.max_hashtags'));
        $this->assertEquals(280, $response->json('data.max_characters'));
    }

    public function test_show_returns_blueprint(): void
    {
        $blueprint = Blueprint::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/blueprints/{$blueprint->id}", $this->headers());

        $response->assertStatus(200);
        $this->assertEquals($blueprint->id, $response->json('data.id'));
    }

    public function test_show_returns_404_for_other_users_blueprint(): void
    {
        $blueprint = Blueprint::factory()->create();

        $response = $this->getJson("/api/blueprints/{$blueprint->id}", $this->headers());

        $response->assertStatus(404);
    }

    public function test_update_modifies_blueprint(): void
    {
        $blueprint = Blueprint::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original',
        ]);

        $response = $this->putJson("/api/blueprints/{$blueprint->id}", [
            'name' => 'Updated',
        ], $this->headers());

        $response->assertStatus(200);
        $this->assertEquals('Updated', $response->json('data.name'));

        $this->assertDatabaseHas('blueprints', [
            'id' => $blueprint->id,
            'name' => 'Updated',
        ]);
    }

    public function test_update_returns_404_for_other_users_blueprint(): void
    {
        $blueprint = Blueprint::factory()->create(['name' => 'Original']);

        $response = $this->putJson("/api/blueprints/{$blueprint->id}", [
            'name' => 'Updated',
        ], $this->headers());

        $response->assertStatus(404);
    }

    public function test_destroy_deletes_blueprint(): void
    {
        $blueprint = Blueprint::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/blueprints/{$blueprint->id}", [], $this->headers());

        $response->assertStatus(204);

        $this->assertDatabaseMissing('blueprints', ['id' => $blueprint->id]);
    }

    public function test_destroy_returns_404_for_other_users_blueprint(): void
    {
        $blueprint = Blueprint::factory()->create();

        $response = $this->deleteJson("/api/blueprints/{$blueprint->id}", [], $this->headers());

        $response->assertStatus(404);
    }

    public function test_index_has_posts_count(): void
    {
        $blueprint = Blueprint::factory()
            ->has(RawContent::factory())
            ->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/blueprints', $this->headers());

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.0.posts_count'));
    }

    public function test_all_endpoints_require_auth(): void
    {
        $this->getJson('/api/blueprints')->assertStatus(401);
        $this->postJson('/api/blueprints', [])->assertStatus(401);
        $this->getJson('/api/blueprints/1')->assertStatus(401);
        $this->putJson('/api/blueprints/1', [])->assertStatus(401);
        $this->deleteJson('/api/blueprints/1')->assertStatus(401);
    }
}

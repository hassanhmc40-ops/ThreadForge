<?php

namespace Tests\Feature;

use App\Models\Blueprint;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Blueprint $blueprint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->blueprint = Blueprint::factory()->create(['user_id' => $this->user->id]);
    }

    public function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_repurpose_returns_202_and_creates_raw_content(): void
    {
        $response = $this->postJson('/api/content/repurpose', [
            'blueprint_id' => $this->blueprint->id,
            'contenu_brut' => '# Hello World This is a test post about Laravel.',
        ], $this->headers());

        $response->assertStatus(202)
            ->assertJsonStructure([
                'message',
                'raw_content_id',
            ]);

        $id = $response->json('raw_content_id');
        $this->assertDatabaseHas('raw_contents', ['id' => $id, 'user_id' => $this->user->id]);
    }

    public function test_repurpose_validates_required_fields(): void
    {
        $response = $this->postJson('/api/content/repurpose', [], $this->headers());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['blueprint_id', 'contenu_brut']);
    }

    public function test_repurpose_validates_blueprint_exists(): void
    {
        $response = $this->postJson('/api/content/repurpose', [
            'blueprint_id' => 999,
            'contenu_brut' => 'Test content',
        ], $this->headers());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['blueprint_id']);
    }

    public function test_index_returns_raw_contents(): void
    {
        RawContent::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/content', $this->headers());

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_only_returns_owned_contents(): void
    {
        RawContent::factory()->create(['user_id' => $this->user->id]);
        RawContent::factory()->create();

        $response = $this->getJson('/api/content', $this->headers());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_status(): void
    {
        RawContent::factory()->create([
            'user_id' => $this->user->id,
            'statut' => 'en_attente',
        ]);
        RawContent::factory()->create([
            'user_id' => $this->user->id,
            'statut' => 'completed',
        ]);

        $response = $this->getJson('/api/content?statut=completed', $this->headers());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('completed', $response->json('data.0.statut'));
    }

    public function test_show_returns_raw_content(): void
    {
        $rawContent = RawContent::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/content/{$rawContent->id}", $this->headers());

        $response->assertStatus(200);
        $this->assertEquals($rawContent->id, $response->json('data.id'));
    }

    public function test_show_returns_404_for_other_users_content(): void
    {
        $rawContent = RawContent::factory()->create();

        $response = $this->getJson("/api/content/{$rawContent->id}", $this->headers());

        $response->assertStatus(404);
    }
}

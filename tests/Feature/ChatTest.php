<?php

namespace Tests\Feature;

use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private GeneratedPost $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $rawContent = RawContent::factory()->create(['user_id' => $this->user->id]);
        $this->post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);
    }

    public function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_chat_validates_message_required(): void
    {
        $response = $this->postJson("/api/posts/{$this->post->id}/chat", [], $this->headers());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_chat_returns_404_for_other_users_post(): void
    {
        $otherPost = GeneratedPost::factory()->create(); // another user

        $response = $this->postJson("/api/posts/{$otherPost->id}/chat", [
            'message' => 'Improve this post',
        ], $this->headers());

        $response->assertStatus(404);
    }

    public function test_history_returns_empty_when_no_conversation(): void
    {
        $response = $this->getJson("/api/posts/{$this->post->id}/chat", $this->headers());

        $response->assertStatus(200)
            ->assertJson([]);
    }

    public function test_history_returns_404_for_other_users_post(): void
    {
        $otherPost = GeneratedPost::factory()->create();

        $response = $this->getJson("/api/posts/{$otherPost->id}/chat", $this->headers());

        $response->assertStatus(404);
    }

    public function test_all_chat_endpoints_require_auth(): void
    {
        $this->postJson("/api/posts/{$this->post->id}/chat", [])->assertStatus(401);
        $this->getJson("/api/posts/{$this->post->id}/chat")->assertStatus(401);
    }
}

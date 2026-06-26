<?php

namespace App\Http\Controllers;

use App\Agents\GhostwriterAgent;
use App\Http\Requests\ChatRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\AgentConversation;
use App\Models\GeneratedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Laravel\Ai\Enums\Lab;

class ChatController extends Controller
{
    public function chat(ChatRequest $request, int $id): JsonResponse
    {
        $post = GeneratedPost::with('rawContent.blueprint')
            ->whereHas('rawContent', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->findOrFail($id);

        $blueprint = $post->rawContent?->blueprint;

        $systemPrompt = sprintf(
            "You are a Ghostwriter Assistant helping a creator refine their X/Twitter post. "
            . "Here is the current post context:\n\n"
            . "--- POST ---\n"
            . "ID: %d\n"
            . "Hook: %s\n"
            . "Body points: %s\n"
            . "Readability score: %d/100\n"
            . "Hashtags: %s\n"
            . "Status: %s\n"
            . "--- RAW CONTENT ---\n%s\n"
            . "%s"
            . "\n\nYou have access to tools that can retrieve additional context. "
            . "Use the tools by passing the ID values shown above. "
            . "Help the user improve this post, suggest alternative hooks, adjust tone, or refine the content.",
            $post->id,
            $post->hook_propose,
            implode(', ', (array) $post->body_points),
            $post->technical_readability_score,
            implode(', ', (array) $post->suggested_hashtags),
            $post->statut,
            $post->rawContent?->contenu_brut ?? 'N/A',
            $blueprint ? "--- BLUEPRINT ---\nID: {$blueprint->id}\nTone: {$blueprint->tone}\nMax hashtags: {$blueprint->max_hashtags}\nMax chars: {$blueprint->max_characters}" : ''
        );

        $agent = new GhostwriterAgent(
            instructions: $systemPrompt,
            tools: [
                new \App\Tools\GetCampaignRules,
                new \App\Tools\GetPostHistory,
            ],
        );

        $agent->forUser(auth()->user());

        $conversation = AgentConversation::where('generated_post_id', $post->id)->first();

        if ($conversation) {
            $agent->continue($conversation->id, auth()->user());
        }

        try {
            $response = $agent->prompt(
                prompt: $request->message,
                provider: Lab::Groq,
                model: config('ai.providers.groq.model', env('GROQ_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct')),
            );

            $conversationId = $response->conversationId ?? $agent->currentConversation();

            if ($conversationId && !$conversation) {
                AgentConversation::where('id', $conversationId)
                    ->update(['generated_post_id' => $post->id]);
            }

            return response()->json([
                'conversation_id' => $conversationId,
                'message' => [
                    'role' => 'assistant',
                    'content' => $response->text,
                ],
            ]);
        } catch (\Exception $e) {
            $conversationId = $agent->currentConversation();

            return response()->json([
                'conversation_id' => $conversationId,
                'error' => 'Failed to generate response: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function history(int $id): AnonymousResourceCollection
    {
        $post = GeneratedPost::with('rawContent')
            ->whereHas('rawContent', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->findOrFail($id);

        $conversation = AgentConversation::where('generated_post_id', $post->id)->first();

        if (!$conversation) {
            return ChatMessageResource::collection(collect());
        }

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get();

        return ChatMessageResource::collection($messages);
    }
}

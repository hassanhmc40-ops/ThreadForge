<?php

namespace App\Tools;

use App\Models\GeneratedPost;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetPostHistory implements Tool
{
    public function description(): string
    {
        return 'Get the content and metadata of a generated post by its ID. Returns the hook, body points, readability score, hashtags, and status.';
    }

    public function handle(Request $request): string
    {
        $post = GeneratedPost::with('rawContent.blueprint')
            ->whereHas('rawContent', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->find((int) $request['post_id']);

        if (!$post) {
            return json_encode(['error' => 'Post not found with the given ID.']);
        }

        return json_encode([
            'id' => $post->id,
            'hook_propose' => $post->hook_propose,
            'body_points' => $post->body_points,
            'technical_readability_score' => $post->technical_readability_score,
            'suggested_hashtags' => $post->suggested_hashtags,
            'tone_compliance_justification' => $post->tone_compliance_justification,
            'statut' => $post->statut,
            'raw_content' => $post->rawContent?->contenu_brut,
            'blueprint_rules' => $post->rawContent?->blueprint ? [
                'tone' => $post->rawContent->blueprint->tone,
                'max_hashtags' => $post->rawContent->blueprint->max_hashtags,
                'max_characters' => $post->rawContent->blueprint->max_characters,
            ] : null,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->integer()
                ->description('The ID of the generated post to retrieve.')
                ->required(),
        ];
    }
}

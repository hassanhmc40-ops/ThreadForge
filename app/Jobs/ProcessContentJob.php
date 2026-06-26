<?php

namespace App\Jobs;

use App\Models\GeneratedPost;
use App\Models\RawContent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\StructuredAnonymousAgent;

class ProcessContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public RawContent $rawContent
    ) {}

    public function handle(): void
    {
        $this->rawContent->update(['statut' => 'processing']);

        try {
            $this->rawContent->load('blueprint');
            $blueprint = $this->rawContent->blueprint;

            $systemPrompt = sprintf(
                "You are a tech content creator specializing in X/Twitter posts. "
                . "Generate a tweet from the provided raw content following these style rules:\n"
                . "- Tone: %s\n"
                . "- Maximum hashtags: %d\n"
                . "- Maximum characters: %d\n"
                . "%s"
                . "\nRespond with the exact structured format requested.",
                $blueprint->tone,
                $blueprint->max_hashtags,
                $blueprint->max_characters,
                $blueprint->regles_supplementaires ? "- Extra rules: {$blueprint->regles_supplementaires}\n" : ''
            );

            $agent = new StructuredAnonymousAgent(
                instructions: $systemPrompt,
                messages: [],
                tools: [],
                schema: fn (JsonSchema $schema) => [
                    'hook_propose' => $schema->string()->required(),
                    'body_points' => $schema->array()->items($schema->string())->required(),
                    'technical_readability_score' => $schema->integer()->required(),
                    'suggested_hashtags' => $schema->array()->items($schema->string())->required(),
                    'tone_compliance_justification' => $schema->string()->required(),
                ],
            );

            $response = $agent->prompt(
                prompt: "Raw content to transform:\n\n" . $this->rawContent->contenu_brut,
                provider: Lab::Groq,
                model: env('GROQ_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct'),
            );

            $data = $response->toArray();

            $this->validateResponse($data);

            GeneratedPost::create([
                'raw_content_id' => $this->rawContent->id,
                'hook_propose' => $data['hook_propose'],
                'body_points' => $data['body_points'],
                'technical_readability_score' => $data['technical_readability_score'],
                'suggested_hashtags' => $data['suggested_hashtags'],
                'tone_compliance_justification' => $data['tone_compliance_justification'],
                'payload_brut' => $data,
                'statut' => 'draft',
            ]);

            $this->rawContent->update(['statut' => 'completed']);
        } catch (\Exception $e) {
            Log::error('Content processing failed: ' . $e->getMessage(), [
                'raw_content_id' => $this->rawContent->id,
                'exception' => $e,
            ]);
            $this->rawContent->update(['statut' => 'failed']);
        }
    }

    private function validateResponse(array $response): void
    {
        $requiredKeys = [
            'hook_propose',
            'body_points',
            'technical_readability_score',
            'suggested_hashtags',
            'tone_compliance_justification',
        ];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $response)) {
                throw new \RuntimeException("Missing required key: {$key}");
            }
        }
    }
}

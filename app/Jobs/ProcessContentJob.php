<?php

namespace App\Jobs;

use App\Models\GeneratedPost;
use App\Models\RawContent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessContentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RawContent $rawContent
    ) {}

    public function handle(): void
    {
        $this->rawContent->update(['statut' => 'processing']);

        try {
            $response = $this->callGrokApi($this->rawContent->contenu_brut);

            $this->validateResponse($response);

            GeneratedPost::create([
                'raw_content_id' => $this->rawContent->id,
                'hook_propose' => $response['hook_propose'],
                'body_points' => $response['body_points'],
                'technical_readability_score' => $response['technical_readability_score'],
                'suggested_hashtags' => $response['suggested_hashtags'],
                'tone_compliance_justification' => $response['tone_compliance_justification'],
                'payload_brut' => $response,
                'statut' => 'draft',
            ]);

            $this->rawContent->update(['statut' => 'completed']);
        } catch (\Exception $e) {
            Log::error('Content processing failed: ' . $e->getMessage());
            $this->rawContent->update(['statut' => 'failed']);
        }
    }

    private function callGrokApi(string $content): array
    {
        // TODO: Implement Grok API call via laravel/ai SDK
        // For now, return a mock response that matches the schema
        return [
            'hook_propose' => substr('Check out this amazing tech insight: ' . $content, 0, 280),
            'body_points' => ['Point 1: Key finding', 'Point 2: Technical detail'],
            'technical_readability_score' => 75,
            'suggested_hashtags' => ['#Tech', '#Dev'],
            'tone_compliance_justification' => 'Professional tone maintained throughout.',
        ];
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

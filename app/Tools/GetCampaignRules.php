<?php

namespace App\Tools;

use App\Models\Blueprint;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetCampaignRules implements Tool
{
    public function description(): string
    {
        return 'Get the style rules for a blueprint by its ID. Returns tone, max hashtags, max characters, and any extra rules.';
    }

    public function handle(Request $request): string
    {
        $blueprint = Blueprint::where('user_id', auth()->id())
            ->find((int) $request['blueprint_id']);

        if (!$blueprint) {
            return json_encode(['error' => 'Blueprint not found with the given ID.']);
        }

        return json_encode([
            'tone' => $blueprint->tone,
            'max_hashtags' => $blueprint->max_hashtags,
            'max_characters' => $blueprint->max_characters,
            'regles_supplementaires' => $blueprint->regles_supplementaires,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'blueprint_id' => $schema->integer()
                ->description('The ID of the blueprint to retrieve rules for.')
                ->required(),
        ];
    }
}

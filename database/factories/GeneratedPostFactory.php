<?php

namespace Database\Factories;

use App\Models\GeneratedPost;
use App\Models\RawContent;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeneratedPostFactory extends Factory
{
    protected $model = GeneratedPost::class;

    public function definition(): array
    {
        return [
            'raw_content_id' => RawContent::factory(),
            'hook_propose' => fake()->sentence(),
            'body_points' => fake()->sentences(3),
            'technical_readability_score' => fake()->numberBetween(50, 100),
            'suggested_hashtags' => ['#' . fake()->word(), '#' . fake()->word()],
            'tone_compliance_justification' => fake()->paragraph(),
            'payload_brut' => null,
            'statut' => 'draft',
        ];
    }
}

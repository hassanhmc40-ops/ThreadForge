<?php

namespace Database\Factories;

use App\Models\Blueprint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlueprintFactory extends Factory
{
    protected $model = Blueprint::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word() . ' Style',
            'tone' => fake()->randomElement(['professional', 'casual', 'humorous', 'technical']),
            'max_hashtags' => fake()->numberBetween(1, 5),
            'max_characters' => fake()->randomElement([280, 400, 500]),
            'regles_supplementaires' => null,
        ];
    }
}

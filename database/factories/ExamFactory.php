<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'instructions' => fake()->paragraph(),
            'display_mode' => fake()->randomElement(['all', 'one_at_a_time']),
            'time_limit_minutes' => fake()->optional()->numberBetween(10, 90),
            'autosave_interval_seconds' => 15,
            'show_score_to_student' => fake()->boolean(),
            'show_correct_answers_to_student' => fake()->boolean(),
            'disable_copy_paste' => true,
            'is_published' => true,
            'published_at' => now(),
            'settings' => [],
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'position' => fake()->numberBetween(1, 10),
            'prompt' => fake()->sentence(),
            'help_text' => fake()->sentence(),
            'points' => 1,
            'allows_multiple_selection' => false,
            'accepted_answers' => [],
            'settings' => [],
        ];
    }

    public function fillIn(): static
    {
        return $this->state(fn () => [
            'type' => Question::TYPE_FILL_IN,
            'accepted_answers' => ['laravel'],
        ]);
    }
}

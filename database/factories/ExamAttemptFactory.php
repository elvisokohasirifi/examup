<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
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
            'exam_access_link_id' => ExamAccessLink::factory(),
            'status' => ExamAttempt::STATUS_IN_PROGRESS,
            'student_name' => fake()->name(),
            'student_email' => fake()->safeEmail(),
            'student_index_number' => fake()->bothify('IDX-####'),
            'started_at' => now(),
            'score' => 0,
            'max_score' => 0,
            'score_percentage' => 0,
            'meta' => [],
        ];
    }
}

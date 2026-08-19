<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamAccessLink>
 */
class ExamAccessLinkFactory extends Factory
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
            'created_by' => User::factory(),
            'public_key' => (string) Str::uuid(),
            'access_token' => Str::random(48),
            'email' => fake()->safeEmail(),
            'max_attempts' => 1,
            'send_email' => false,
            'is_active' => true,
            'expires_at' => now()->addDay(),
            'meta' => [],
        ];
    }
}

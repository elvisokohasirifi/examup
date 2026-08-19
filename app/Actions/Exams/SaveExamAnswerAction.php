<?php

namespace App\Actions\Exams;

use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;

class SaveExamAnswerAction
{
    public function handle(ExamAttempt $attempt, Question $question, array $payload): ExamAnswer
    {
        return ExamAnswer::updateOrCreate(
            [
                'exam_attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            [
                'answer_text' => $payload['answer_text'] ?? null,
                'selected_option_ids' => $payload['selected_option_ids'] ?? null,
                'answered_at' => now(),
            ],
        );
    }
}

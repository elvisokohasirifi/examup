<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Models\Concerns\UsesUuid;
use Database\Factories\ExamAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

class ExamAnswer extends Model
{
    /** @use HasFactory<ExamAnswerFactory> */
    use HasFactory;

    use LogsModelActivity;
    use UsesUuid;

    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'answer_text',
        'selected_option_ids',
        'graded_payload',
        'is_correct',
        'score',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'graded_payload' => 'array',
            'is_correct' => 'boolean',
            'score' => 'decimal:2',
            'answered_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function formattedScore(): string
    {
        $score = round((float) $this->score, 1);

        if ((float) ((int) $score) === $score) {
            return (string) ((int) $score);
        }

        return Number::format($score, precision: 1);
    }
}

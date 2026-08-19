<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Database\Factories\SuspiciousActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspiciousActivity extends Model
{
    /** @use HasFactory<SuspiciousActivityFactory> */
    use HasFactory;

    use UsesUuid;

    protected $fillable = [
        'exam_attempt_id',
        'event_type',
        'severity',
        'details',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
}

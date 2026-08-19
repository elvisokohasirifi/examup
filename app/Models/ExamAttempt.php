<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Models\Concerns\UsesUuid;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use CrudTrait;

    use HasFactory;
    use LogsModelActivity;
    use UsesUuid;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_AUTO_SUBMITTED = 'auto_submitted';

    protected $fillable = [
        'exam_id',
        'exam_access_link_id',
        'status',
        'student_name',
        'student_email',
        'student_index_number',
        'access_token_hash',
        'started_at',
        'submitted_at',
        'expires_at',
        'auto_submitted_at',
        'duration_seconds',
        'score',
        'max_score',
        'score_percentage',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'expires_at' => 'datetime',
            'auto_submitted_at' => 'datetime',
            'duration_seconds' => 'integer',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'score_percentage' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function accessLink(): BelongsTo
    {
        return $this->belongsTo(ExamAccessLink::class, 'exam_access_link_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function suspiciousActivities(): HasMany
    {
        return $this->hasMany(SuspiciousActivity::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_AUTO_SUBMITTED], true);
    }

    public function formattedScore(): string
    {
        return self::formatDisplayNumber($this->score);
    }

    public function formattedMaxScore(): string
    {
        return self::formatDisplayNumber($this->max_score);
    }

    public function formattedScorePercentage(): string
    {
        return self::formatDisplayNumber($this->score_percentage);
    }

    private static function formatDisplayNumber(mixed $value): string
    {
        $number = round((float) $value, 1);

        if ((float) ((int) $number) === $number) {
            return (string) ((int) $number);
        }

        return Number::format($number, precision: 1);
    }
}

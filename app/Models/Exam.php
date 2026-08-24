<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Models\Concerns\UsesUuid;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use CrudTrait;

    use HasFactory;
    use LogsModelActivity;
    use UsesUuid;

    protected $fillable = [
        'created_by',
        'title',
        'description',
        'instructions',
        'display_mode',
        'allow_back_navigation',
        'shuffle_questions',
        'time_limit_minutes',
        'autosave_interval_seconds',
        'show_score_to_student',
        'show_correct_answers_to_student',
        'show_index_number_field',
        'disable_copy_paste',
        'is_published',
        'published_at',
        'expires_at',
        'settings',
    ];

    protected static function booted(): void
    {
        static::creating(function (Exam $exam): void {
            if ($exam->created_by === null) {
                $exam->created_by = backpack_user()?->id ?? auth()->id();
            }
        });

        static::updated(function (Exam $exam): void {
            if (! $exam->wasChanged('expires_at')) {
                return;
            }

            $exam->accessLinks()
                ->where('meta->shareable', true)
                ->update(['expires_at' => $exam->expires_at]);
        });
    }

    protected function casts(): array
    {
        return [
            'time_limit_minutes' => 'integer',
            'autosave_interval_seconds' => 'integer',
            'allow_back_navigation' => 'boolean',
            'shuffle_questions' => 'boolean',
            'show_score_to_student' => 'boolean',
            'show_correct_answers_to_student' => 'boolean',
            'show_index_number_field' => 'boolean',
            'disable_copy_paste' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function accessLinks(): HasMany
    {
        return $this->hasMany(ExamAccessLink::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function answers(): HasManyThrough
    {
        return $this->hasManyThrough(ExamAnswer::class, ExamAttempt::class);
    }

    public function totalPoints(): float
    {
        return (float) $this->questions->sum('points');
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

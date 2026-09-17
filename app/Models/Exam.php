<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Models\Concerns\UsesUuid;
use App\Support\Tenancy;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;

class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use CrudTrait;

    use HasFactory;
    use LogsModelActivity;
    use UsesUuid;

    protected $fillable = [
        'created_by',
        'organization_id',
        'department_id',
        'title',
        'description',
        'instructions',
        'display_mode',
        'allow_back_navigation',
        'shuffle_questions',
        'questions_per_attempt',
        'time_limit_minutes',
        'autosave_interval_seconds',
        'show_score_to_student',
        'show_correct_answers_to_student',
        'show_index_number_field',
        'disable_copy_paste',
        'require_fullscreen',
        'is_published',
        'published_at',
        'expires_at',
        'settings',
    ];

    protected static function booted(): void
    {
        static::creating(function (Exam $exam): void {
            $user = backpack_user() ?? auth()->user();

            if ($exam->created_by === null) {
                $exam->created_by = $user?->id;
            }

            if (Tenancy::enabled() && $user instanceof User) {
                $exam->organization_id ??= $user->organization_id;
                $exam->department_id ??= $user->department_id;
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
            'questions_per_attempt' => 'integer',
            'show_score_to_student' => 'boolean',
            'show_correct_answers_to_student' => 'boolean',
            'show_index_number_field' => 'boolean',
            'disable_copy_paste' => 'boolean',
            'require_fullscreen' => 'boolean',
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! Tenancy::enabled()) {
            if ($user->isExaminer()) {
                $query->where('created_by', $user->id);
            }

            return;
        }

        if ($user->isPlatformAdmin()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where('organization_id', $user->organization_id);

        if (! $user->isOrganizationAdmin()) {
            if ($user->department_id === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('department_id', $user->department_id);
        }
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

    public function usesPerQuestionTimer(): bool
    {
        return $this->display_mode === 'one_at_a_time'
            && ! $this->allow_back_navigation
            && filter_var(Arr::get($this->settings, 'enable_per_question_timer', false), FILTER_VALIDATE_BOOL);
    }
}

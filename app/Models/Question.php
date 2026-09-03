<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Models\Concerns\UsesUuid;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use CrudTrait;

    use HasFactory;
    use LogsModelActivity;
    use UsesUuid;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_FILL_IN = 'fill_in';

    protected $fillable = [
        'exam_id',
        'type',
        'position',
        'prompt',
        'help_text',
        'points',
        'allows_multiple_selection',
        'accepted_answers',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'points' => 'decimal:2',
            'allows_multiple_selection' => 'boolean',
            'accepted_answers' => 'array',
            'settings' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    public function isMultipleChoice(): bool
    {
        return $this->type === self::TYPE_MULTIPLE_CHOICE;
    }

    public function isFillIn(): bool
    {
        return $this->type === self::TYPE_FILL_IN;
    }

    public function formattedPoints(): string
    {
        return self::formatDisplayNumber($this->points);
    }

    public function normalizedAcceptedAnswers(): Collection
    {
        return collect($this->accepted_answers)
            ->filter()
            ->map(fn (string $answer): string => Str::lower(Str::squish($answer)))
            ->values();
    }

    public function timeLimitSeconds(): ?int
    {
        $timeLimitSeconds = Arr::get($this->settings, 'time_limit_seconds');

        if (! is_numeric($timeLimitSeconds)) {
            return null;
        }

        $timeLimitSeconds = (int) $timeLimitSeconds;

        return $timeLimitSeconds >= 1 ? $timeLimitSeconds : null;
    }

    public function setAcceptedAnswersAttribute(mixed $value): void
    {
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value) ?: [];
        }

        $this->attributes['accepted_answers'] = json_encode(
            collect(Arr::wrap($value))
                ->map(fn ($answer) => trim((string) $answer))
                ->map(fn (string $answer): string => Str::squish($answer))
                ->filter()
                ->values()
                ->all(),
            JSON_THROW_ON_ERROR,
        );
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

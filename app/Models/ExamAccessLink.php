<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Models\Concerns\UsesUuid;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\ExamAccessLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ExamAccessLink extends Model
{
    /** @use HasFactory<ExamAccessLinkFactory> */
    use CrudTrait;

    use HasFactory;
    use LogsModelActivity;
    use UsesUuid;

    protected $fillable = [
        'exam_id',
        'created_by',
        'public_key',
        'access_token',
        'email',
        'max_attempts',
        'send_email',
        'is_active',
        'expires_at',
        'last_sent_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'max_attempts' => 'integer',
            'send_email' => 'boolean',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $link): void {
            $link->public_key ??= (string) Str::uuid();
            $link->access_token ??= Str::random(48);
        });
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function remainingAttempts(): ?int
    {
        if ($this->max_attempts < 1) {
            return null;
        }

        return max($this->max_attempts - $this->attempts()->count(), 0);
    }

    public function isAvailable(): bool
    {
        return $this->is_active
            && ! $this->hasExpired()
            && ($this->max_attempts < 1 || $this->attempts()->count() < $this->max_attempts);
    }

    public function isPreview(): bool
    {
        return (bool) data_get($this->meta, 'is_preview', false);
    }

    public function examUrl(): string
    {
        return route('exam.take', [
            'publicKey' => $this->public_key,
            'accessToken' => $this->access_token,
        ]);
    }
}

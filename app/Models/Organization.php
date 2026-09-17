<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Models\Concerns\UsesUuid;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use LogsModelActivity;
    use UsesUuid;

    protected $fillable = ['name', 'slug', 'is_frozen'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_frozen' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Models\Concerns\UsesUuid;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use CrudTrait;

    use HasFactory;
    use LogsModelActivity;
    use UsesUuid;

    protected $fillable = ['organization_id', 'name', 'slug'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}

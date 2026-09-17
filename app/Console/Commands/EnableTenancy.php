<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('examup:enable-tenancy')]
#[Description('Assign existing data to the legacy ExamUP organisation before enabling tenancy.')]
class EnableTenancy extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = (string) config('examup.tenancy.legacy_organization_name');

        DB::transaction(function () use ($name): void {
            $organization = Organization::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );

            User::query()->whereNull('organization_id')->update(['organization_id' => $organization->id]);
            Exam::query()->whereNull('organization_id')->update(['organization_id' => $organization->id]);
            User::query()->where('role', User::ROLE_ADMIN)->orderBy('created_at')->limit(1)->update(['is_platform_admin' => true]);
        });

        $this->components->info('Legacy data is assigned. Set IS_TENANT=true and clear the configuration cache.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Actions\Tenancy;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterOrganizationAction
{
    /**
     * @param  array{name: string, email: string, password: string, organization_name: string}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $organization = Organization::create([
                'name' => $data['organization_name'],
                'slug' => $this->uniqueSlug($data['organization_name']),
            ]);

            return User::create([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'password' => $data['password'],
                'role' => User::ROLE_ADMIN,
                'organization_id' => $organization->id,
            ]);
        });
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name) ?: 'organization';
        $candidate = $slug;
        $suffix = 2;

        while (Organization::query()->where('slug', $candidate)->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}

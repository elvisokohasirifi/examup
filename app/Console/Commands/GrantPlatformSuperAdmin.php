<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('examup:grant-platform-super-admin {email : The globally unique user email address}')]
#[Description('Grant a user platform super-admin access across every ExamUP organisation.')]
class GrantPlatformSuperAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user instanceof User) {
            $this->components->error('No user was found with that email address.');

            return self::FAILURE;
        }

        $user->update(['role' => User::ROLE_ADMIN, 'is_platform_admin' => true]);
        $this->components->info("{$user->email} is now a platform super-admin.");

        return self::SUCCESS;
    }
}

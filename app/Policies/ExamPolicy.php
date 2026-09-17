<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use App\Support\Tenancy;

class ExamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessBackOffice();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Exam $exam): bool
    {
        if (Tenancy::enabled()) {
            return $this->sharesVisibleTenant($user, $exam);
        }

        return $user->isAdmin() || $exam->created_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isExaminer();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Exam $exam): bool
    {
        if (Tenancy::enabled()) {
            if (! $this->sharesVisibleTenant($user, $exam)) {
                return false;
            }

            return $user->isPlatformAdmin()
                || $user->isOrganizationAdmin()
                || $user->isDepartmentAdmin()
                || $exam->created_by === $user->id;
        }

        return $user->isAdmin() || $exam->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Exam $exam): bool
    {
        return $user->isAdmin() || $exam->created_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Exam $exam): bool
    {
        return $this->delete($user, $exam);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Exam $exam): bool
    {
        return Tenancy::enabled()
            ? $user->isPlatformAdmin() || ($user->isOrganizationAdmin() && $user->organization_id === $exam->organization_id)
            : $user->isAdmin();
    }

    private function sharesVisibleTenant(User $user, Exam $exam): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->organization_id === null || $user->organization_id !== $exam->organization_id) {
            return false;
        }

        return $user->isOrganizationAdmin()
            || ($user->department_id !== null && $user->department_id === $exam->department_id);
    }
}

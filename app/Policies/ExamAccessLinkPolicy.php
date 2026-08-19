<?php

namespace App\Policies;

use App\Models\ExamAccessLink;
use App\Models\User;

class ExamAccessLinkPolicy
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
    public function view(User $user, ExamAccessLink $examAccessLink): bool
    {
        return $user->isAdmin() || $examAccessLink->exam->created_by === $user->id;
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
    public function update(User $user, ExamAccessLink $examAccessLink): bool
    {
        return $user->isAdmin() || $examAccessLink->exam->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExamAccessLink $examAccessLink): bool
    {
        return $user->isAdmin() || $examAccessLink->exam->created_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExamAccessLink $examAccessLink): bool
    {
        return $this->delete($user, $examAccessLink);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExamAccessLink $examAccessLink): bool
    {
        return $user->isAdmin();
    }
}

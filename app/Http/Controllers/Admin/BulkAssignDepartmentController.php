<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkAssignDepartmentRequest;
use App\Models\Department;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BulkAssignDepartmentController extends Controller
{
    public function create(): View
    {
        abort_unless(Tenancy::enabled() && backpack_user()->canManageUsers(), 403);

        return view('admin.users.assign-department', [
            'departments' => $this->visibleDepartments(),
            'users' => $this->visibleUsers()->with('department')->orderBy('name')->get(),
        ]);
    }

    public function store(BulkAssignDepartmentRequest $request): RedirectResponse
    {
        abort_unless(Tenancy::enabled() && backpack_user()->canManageUsers(), 403);

        $department = Department::query()->findOrFail($request->input('department_id'));
        $actor = backpack_user();

        abort_unless(
            $actor->isPlatformAdmin() || $department->organization_id === $actor->organization_id,
            403,
        );

        $users = $this->visibleUsers()->whereIn('id', $request->input('user_ids'))->get();
        abort_unless($users->count() === count($request->input('user_ids')), 403);

        DB::transaction(function () use ($users, $department, $request, $actor): void {
            foreach ($users as $user) {
                $user->update([
                    'organization_id' => $department->organization_id,
                    'department_id' => $department->id,
                    'is_department_admin' => $actor->isDepartmentAdmin()
                        ? false
                        : $request->boolean('make_department_admins'),
                ]);
            }
        });

        return redirect()->route('admin.users.assign-department')->with('status', 'Selected users were assigned to the department.');
    }

    private function visibleUsers()
    {
        $query = User::query();
        $actor = backpack_user();

        if (! $actor->isPlatformAdmin()) {
            $query->where('organization_id', $actor->organization_id);
        }

        if ($actor->isDepartmentAdmin()) {
            $query->where('department_id', $actor->department_id);
        }

        return $query;
    }

    private function visibleDepartments()
    {
        $query = Department::query()->orderBy('name');

        if (! backpack_user()->isPlatformAdmin()) {
            $query->where('organization_id', backpack_user()->organization_id);
        }

        return $query->get();
    }
}

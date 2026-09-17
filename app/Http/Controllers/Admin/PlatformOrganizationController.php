<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\Tenancy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PlatformOrganizationController extends Controller
{
    public function index(): View
    {
        $this->authorizePlatformAccess();

        return view('admin.organizations.index', [
            'organizations' => Organization::query()
                ->withCount(['users', 'departments', 'exams'])
                ->latest()
                ->paginate(25),
        ]);
    }

    public function freeze(Organization $organization): RedirectResponse
    {
        $this->authorizePlatformAccess();
        $organization->update(['is_frozen' => true]);

        return back()->with('success', "{$organization->name} has been frozen.");
    }

    public function unfreeze(Organization $organization): RedirectResponse
    {
        $this->authorizePlatformAccess();
        $organization->update(['is_frozen' => false]);

        return back()->with('success', "{$organization->name} has been unfrozen.");
    }

    private function authorizePlatformAccess(): void
    {
        abort_unless(Tenancy::enabled() && backpack_user()?->isPlatformAdmin(), 403);
    }
}

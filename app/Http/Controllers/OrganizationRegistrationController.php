<?php

namespace App\Http\Controllers;

use App\Actions\Tenancy\RegisterOrganizationAction;
use App\Http\Requests\RegisterOrganizationRequest;
use App\Support\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationRegistrationController extends Controller
{
    public function create(): View
    {
        abort_unless(Tenancy::enabled(), 404);

        return view('auth.register-organization');
    }

    public function store(RegisterOrganizationRequest $request, RegisterOrganizationAction $registerOrganization): RedirectResponse
    {
        $user = $registerOrganization->handle($request->validated());

        backpack_auth()->login($user);
        $request->session()->regenerate();

        return redirect()->to(backpack_url('dashboard'));
    }
}

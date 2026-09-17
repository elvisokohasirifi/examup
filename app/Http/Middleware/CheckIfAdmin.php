<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckIfAdmin
{
    /**
     * Checked that the logged in user is an administrator.
     *
     * --------------
     * VERY IMPORTANT
     * --------------
     * If you have both regular users and admins inside the same table, change
     * the contents of this method to check that the logged in user
     * is an admin, and not a regular user.
     *
     * Additionally, in Laravel 7+, you should change app/Providers/RouteServiceProvider::HOME
     * which defines the route where a logged in user (but not admin) gets redirected
     * when trying to access an admin route. By default it's '/home' but Backpack
     * does not have a '/home' route, use something you've built for your users
     * (again - users, not admins).
     *
     * @param  ?Authenticatable  $user
     */
    private function checkIfUserIsAdmin($user, Request $request): bool
    {
        if (! method_exists($user, 'canAccessBackOffice') || ! $user->canAccessBackOffice()) {
            return false;
        }

        if (Tenancy::enabled() && $user->isPlatformAdmin()) {
            $platformOnlyPrefixes = [
                'admin/dashboard',
                'admin/organizations',
                'admin/setting',
                'admin/log',
                'admin/activity-log',
            ];

            foreach ($platformOnlyPrefixes as $prefix) {
                if ($request->is($prefix) || $request->is($prefix.'/*')) {
                    return true;
                }
            }

            return false;
        }

        if (Tenancy::enabled() && $user->organization?->is_frozen) {
            return false;
        }

        if ($user->isAdmin() && ! Tenancy::enabled()) {
            return true;
        }

        if ($request->is('admin/user') || $request->is('admin/user/*')) {
            return $user->canManageUsers();
        }

        if ($request->is('admin/department') || $request->is('admin/department/*')) {
            return $user->canManageDepartments();
        }

        $adminOnlyPrefixes = [
            'admin/user',
            'admin/setting',
            'admin/log',
            'admin/activity-log',
        ];

        foreach ($adminOnlyPrefixes as $prefix) {
            if ($request->is($prefix) || $request->is($prefix.'/*')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Answer to unauthorized access request.
     *
     * @param  Request  $request
     * @return Response|RedirectResponse
     */
    private function respondToUnauthorizedRequest($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response(trans('backpack::base.unauthorized'), 401);
        } else {
            return redirect()->guest(backpack_url('login'));
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (backpack_auth()->guest()) {
            return $this->respondToUnauthorizedRequest($request);
        }

        if (! $this->checkIfUserIsAdmin(backpack_user(), $request)) {
            return $this->respondToUnauthorizedRequest($request);
        }

        return $next($request);
    }
}

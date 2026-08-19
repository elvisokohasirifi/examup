<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleLoginController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->googleLoginIsConfigured()) {
            return $this->redirectToLoginWithError('Google login is not configured yet. Please add your Google OAuth credentials first.');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (! $this->googleLoginIsConfigured()) {
            return $this->redirectToLoginWithError('Google login is not configured yet. Please add your Google OAuth credentials first.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return $this->redirectToLoginWithError('Google sign-in could not be completed. Please try again.');
        }

        $email = $googleUser->getEmail();

        if (! is_string($email) || $email === '') {
            return $this->redirectToLoginWithError('Google did not return an email address for this account.');
        }

        $user = User::query()
            ->where('email', $email)
            ->first();

        if (! $user instanceof User || ! $user->canAccessBackOffice()) {
            return $this->redirectToLoginWithError('No admin or examiner account matches that Google email address.');
        }

        $user->forceFill([
            'name' => $googleUser->getName() ?: $user->name,
            'google_id' => $googleUser->getId() ?: $user->google_id,
            'email_verified_at' => $user->email_verified_at ?? Carbon::now(),
        ])->save();

        backpack_auth()->login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(backpack_url('dashboard'));
    }

    private function googleLoginIsConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function redirectToLoginWithError(string $message): RedirectResponse
    {
        return redirect()
            ->route('backpack.auth.login')
            ->withErrors([
                backpack_authentication_column() => $message,
            ]);
    }
}

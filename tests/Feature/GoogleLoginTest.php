<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config()->set('services.google.client_id', 'google-client-id');
    config()->set('services.google.client_secret', 'google-client-secret');
    config()->set('services.google.redirect', 'http://localhost/admin/auth/google/callback');
});

test('admin login page shows the google sign in button', function () {
    $response = $this->get(backpack_url('login'));

    $response->assertOk()
        ->assertSee('Continue with Google')
        ->assertSee(route('admin.auth.google.redirect'), false);
});

test('existing back office users can log in with google', function () {
    $user = User::factory()->examiner()->create([
        'name' => 'Existing Examiner',
        'email' => 'examiner@example.com',
        'email_verified_at' => null,
    ]);

    $googleUser = Mockery::mock(SocialiteUserContract::class);
    $googleUser->shouldReceive('getEmail')->once()->andReturn('examiner@example.com');
    $googleUser->shouldReceive('getName')->once()->andReturn('Updated Examiner');
    $googleUser->shouldReceive('getId')->once()->andReturn('google-user-123');

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($provider);

    $response = $this->get(route('admin.auth.google.callback'));

    $response->assertRedirect(backpack_url('dashboard'));
    $this->assertAuthenticatedAs($user->fresh(), backpack_guard_name());

    expect($user->fresh())
        ->name->toBe('Updated Examiner')
        ->google_id->toBe('google-user-123')
        ->email_verified_at->not->toBeNull();
});

test('google callback rejects emails without an admin or examiner account', function () {
    $googleUser = Mockery::mock(SocialiteUserContract::class);
    $googleUser->shouldReceive('getEmail')->once()->andReturn('outsider@example.com');

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($provider);

    $response = $this->from(backpack_url('login'))
        ->get(route('admin.auth.google.callback'));

    $response->assertRedirect(route('backpack.auth.login'))
        ->assertSessionHasErrors([
            backpack_authentication_column() => 'No admin or examiner account matches that Google email address.',
        ]);

    $this->assertGuest(backpack_guard_name());
    expect(User::query()->count())->toBe(0);
});

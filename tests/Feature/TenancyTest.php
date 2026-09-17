<?php

use App\Models\Department;
use App\Models\Exam;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config()->set('examup.tenancy.enabled', true);
});

test('a visitor can register an organisation and its first administrator', function () {
    $this->post(route('organization.register.store'), [
        'organization_name' => 'Impact Bible Training Centre',
        'name' => 'Organisation Owner',
        'email' => 'owner@example.com',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ])->assertRedirect(backpack_url('dashboard'));

    $owner = User::query()->sole();

    expect($owner->isOrganizationAdmin())->toBeTrue()
        ->and($owner->organization)->not->toBeNull()
        ->and($owner->organization->name)->toBe('Impact Bible Training Centre');
});

test('organisations and departments cannot view each others exams', function () {
    $firstOrganization = Organization::factory()->create();
    $secondOrganization = Organization::factory()->create();
    $firstDepartment = Department::factory()->for($firstOrganization)->create();
    $secondDepartment = Department::factory()->for($secondOrganization)->create();
    $departmentAdmin = User::factory()->examiner()->create([
        'organization_id' => $firstOrganization->id,
        'department_id' => $firstDepartment->id,
        'is_department_admin' => true,
    ]);
    $firstExam = Exam::factory()->create([
        'created_by' => $departmentAdmin->id,
        'organization_id' => $firstOrganization->id,
        'department_id' => $firstDepartment->id,
    ]);
    $secondExam = Exam::factory()->create([
        'organization_id' => $secondOrganization->id,
        'department_id' => $secondDepartment->id,
    ]);

    expect($departmentAdmin->can('view', $firstExam))->toBeTrue()
        ->and($departmentAdmin->can('view', $secondExam))->toBeFalse()
        ->and(Exam::query()->visibleTo($departmentAdmin)->pluck('id')->all())->toBe([$firstExam->id]);
});

test('a platform super-admin can be granted through the artisan command', function () {
    $user = User::factory()->examiner()->create(['email' => 'platform@example.com']);

    $this->artisan('examup:grant-platform-super-admin', ['email' => $user->email])
        ->assertExitCode(0);

    expect($user->fresh()->isPlatformAdmin())->toBeTrue()
        ->and($user->fresh()->isAdmin())->toBeTrue();
});

test('the tenancy enable command preserves legacy users and exams', function () {
    $legacyAdmin = User::factory()->admin()->create();
    $legacyExam = Exam::factory()->create(['created_by' => $legacyAdmin->id]);

    $this->artisan('examup:enable-tenancy')->assertExitCode(0);

    expect($legacyAdmin->fresh()->organization_id)->not->toBeNull()
        ->and($legacyAdmin->fresh()->isPlatformAdmin())->toBeTrue()
        ->and($legacyExam->fresh()->organization_id)->toBe($legacyAdmin->fresh()->organization_id);
});

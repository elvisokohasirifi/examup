<?php

use App\Livewire\TakeExam;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\UserAddedToOrganizationNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

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

test('a platform super-admin can only access the organisation register', function () {
    $platformAdmin = User::factory()->admin()->create(['is_platform_admin' => true]);
    $organization = Organization::factory()->create();

    $this->actingAs($platformAdmin)
        ->get(route('admin.organizations.index'))
        ->assertOk()
        ->assertSee($organization->name)
        ->assertDontSee('Exam overview');

    $this->actingAs($platformAdmin)
        ->get(backpack_url('exam'))
        ->assertRedirect(backpack_url('login'));
});

test('a platform super-admin can freeze and unfreeze an organisation', function () {
    $platformAdmin = User::factory()->admin()->create(['is_platform_admin' => true]);
    $organization = Organization::factory()->create();

    $this->actingAs($platformAdmin)
        ->post(route('admin.organizations.freeze', $organization))
        ->assertRedirect();

    expect($organization->fresh()->is_frozen)->toBeTrue();

    $this->actingAs($platformAdmin)
        ->post(route('admin.organizations.unfreeze', $organization))
        ->assertRedirect();

    expect($organization->fresh()->is_frozen)->toBeFalse();
});

test('freezing an organisation blocks its staff and student exam links', function () {
    $organization = Organization::factory()->create(['is_frozen' => true]);
    $organizationAdmin = User::factory()->admin()->create(['organization_id' => $organization->id]);
    $exam = Exam::factory()->create(['organization_id' => $organization->id]);
    $link = ExamAccessLink::factory()->create(['exam_id' => $exam->id]);

    $this->actingAs($organizationAdmin)
        ->get(backpack_url('dashboard'))
        ->assertRedirect(backpack_url('login'));

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->assertSet('isUnavailable', true)
        ->assertSee('not currently accepting exam responses');
});

test('staff invitation emails include the tenant context and sign-in details', function () {
    $organization = Organization::factory()->create(['name' => 'Impact Bible Training Centre']);
    $department = Department::factory()->for($organization)->create(['name' => 'Theology']);
    $addedBy = User::factory()->admin()->create([
        'name' => 'Organisation Owner',
        'organization_id' => $organization->id,
    ]);
    $addedUser = User::factory()->examiner()->create([
        'name' => 'New Examiner',
        'organization_id' => $organization->id,
        'department_id' => $department->id,
    ]);

    $mail = (new UserAddedToOrganizationNotification($addedUser, $addedBy))->toMail($addedUser);

    expect($mail->subject)->toContain('Impact Bible Training Centre')
        ->and($mail->introLines)->toContain('Role: Examiner')
        ->and($mail->introLines)->toContain('Department: Theology')
        ->and($mail->actionText)->toBe('Sign in to ExamUP')
        ->and($mail->actionUrl)->toBe(backpack_url('login'));
});

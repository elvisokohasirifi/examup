<?php

use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAttempt;
use App\Models\SuspiciousActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('examiner dashboard only includes their exams and activity', function () {
    $examiner = User::factory()->examiner()->create();
    $otherExaminer = User::factory()->examiner()->create();
    $exam = Exam::factory()->for($examiner, 'creator')->create([
        'title' => 'My live exam',
        'is_published' => true,
        'expires_at' => now()->addDay(),
    ]);
    Exam::factory()->for($otherExaminer, 'creator')->create([
        'title' => 'Another live exam',
        'is_published' => true,
        'expires_at' => now()->addDay(),
    ]);
    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => ExamAccessLink::factory()->create([
            'exam_id' => $exam->id,
            'created_by' => $examiner->id,
        ])->id,
        'student_name' => 'Ama Boateng',
        'status' => ExamAttempt::STATUS_SUBMITTED,
        'submitted_at' => now(),
        'score' => 8,
        'max_score' => 10,
    ]);
    SuspiciousActivity::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'event_type' => 'tab_hidden',
        'severity' => 'medium',
    ]);

    $this->actingAs($examiner)
        ->get(backpack_url('dashboard'))
        ->assertOk()
        ->assertSee('Exam overview')
        ->assertSee('My live exam')
        ->assertSee('Ama Boateng')
        ->assertDontSee('Another live exam');
});

test('landing page highlights the latest exam features', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Question banks')
        ->assertSee('Personal links, timers, auto-save, and retakes.')
        ->assertSee('fullscreen checks, clipboard deterrents');
});

test('exam list action buttons point to access and results pages', function () {
    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create();

    expect(view('vendor.backpack.crud.buttons.exam_manage_access', ['entry' => $exam])->render())
        ->toContain(route('admin.exams.access', $exam))
        ->toContain('Manage access');

    expect(view('vendor.backpack.crud.buttons.exam_view_results', ['entry' => $exam])->render())
        ->toContain(route('admin.exams.results', $exam))
        ->toContain('View results');
});

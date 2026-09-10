<?php

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

test('admin can import Microsoft Forms questions and historical attempts', function () {
    $admin = User::factory()->admin()->create();
    $csv = str_replace('[WINDOWS_DASH]', chr(0x97), <<<'CSV'
ID,Start time,Completion time,Email,Name,Total points,Quiz feedback,Last modified time,STUDENTS FULL NAME,Points - STUDENTS FULL NAME,STUDENT ID NUMBER,Points - STUDENT ID NUMBER,What [WINDOWS_DASH] is grace?,Points - What [WINDOWS_DASH] is grace?,Feedback - What [WINDOWS_DASH] is grace?,Who led Israel out of Egypt?,Points - Who led Israel out of Egypt?
1,7/27/26 19:06:23,7/27/26 19:17:10,anonymous,,4,,,Ama Mensah,,IMPACT-001,,God's unearned favor,2,,Moses,2
2,7/27/26 19:08:00,7/27/26 19:22:00,student@example.com,,2,,,Kojo Owusu,,IMPACT-002,,God's favor,2,,Aaron,0
CSV);
    $file = UploadedFile::fake()->createWithContent('responses.csv', $csv);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.exams.import.microsoft-forms.store'), [
            'title' => 'Advanced Certificate Exams - July 2026',
            'results_file' => $file,
        ]);

    $exam = Exam::query()->sole();

    $response->assertRedirect(route('admin.exams.results', $exam));
    expect($exam->title)->toBe('Advanced Certificate Exams - July 2026')
        ->and(data_get($exam->settings, 'imported_from_microsoft_forms'))->toBeTrue()
        ->and($exam->is_published)->toBeFalse();
    expect($exam->questions)->toHaveCount(2)
        ->and($exam->attempts)->toHaveCount(2)
        ->and($exam->questions()->where('prompt', 'What — is grace?')->exists())->toBeTrue()
        ->and(Activity::query()->where('subject_id', $exam->id)->count())->toBe(1);

    $attempt = $exam->attempts()->where('student_name', 'Kojo Owusu')->sole();
    expect($attempt->status)->toBe(ExamAttempt::STATUS_SUBMITTED)
        ->and((float) $attempt->score)->toBe(2.0)
        ->and($attempt->student_index_number)->toBe('IMPACT-002')
        ->and($attempt->duration_seconds)->toBe(840);

    $answer = ExamAnswer::query()
        ->where('exam_attempt_id', $attempt->id)
        ->whereHas('question', fn ($query) => $query->where('prompt', 'Who led Israel out of Egypt?'))
        ->sole();

    expect($answer->answer_text)->toBe('Aaron')
        ->and((float) $answer->score)->toBe(0.0)
        ->and((float) data_get($answer->graded_payload, 'microsoft_forms_awarded_score'))->toBe(0.0);
});

test('import page is available to examiners', function () {
    $examiner = User::factory()->examiner()->create();

    $this->actingAs($examiner)
        ->get(route('admin.exams.import.microsoft-forms'))
        ->assertOk()
        ->assertSee('Import Microsoft Forms results');
});

test('import detects title-cased first-name and student-id columns', function () {
    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->createWithContent('responses.csv', <<<'CSV'
ID,Start time,Completion time,Email,Name,Total points,Student first name,Points - Student first name,Student ID number,Points - Student ID number,What is faith?,Points - What is faith?
1,7/20/26 20:02:35,7/20/26 20:30:38,anonymous,,2,Akosua,,LEVEL1-001,,Trust in God,2
CSV);

    $this
        ->actingAs($admin)
        ->post(route('admin.exams.import.microsoft-forms.store'), [
            'title' => 'Level 1 Module 1',
            'results_file' => $file,
        ])
        ->assertRedirect();

    $exam = Exam::query()->sole();
    $attempt = $exam->attempts()->sole();

    expect($exam->questions)->toHaveCount(1)
        ->and($exam->questions->sole()->prompt)->toBe('What is faith?')
        ->and($attempt->student_name)->toBe('Akosua')
        ->and($attempt->student_index_number)->toBe('LEVEL1-001');
});

test('import batches large result files without changing the imported results', function () {
    $admin = User::factory()->admin()->create();
    $rows = collect(range(1, 201))
        ->map(fn (int $number): string => "{$number},7/20/26 20:02:35,7/20/26 20:30:38,student{$number}@example.com,,2,Candidate {$number},,INDEX-{$number},,Trust in God,2")
        ->implode("\n");
    $file = UploadedFile::fake()->createWithContent('responses.csv', <<<CSV
ID,Start time,Completion time,Email,Name,Total points,Student first name,Points - Student first name,Student ID number,Points - Student ID number,What is faith?,Points - What is faith?
{$rows}
CSV);

    $this
        ->actingAs($admin)
        ->post(route('admin.exams.import.microsoft-forms.store'), [
            'title' => 'Large Level 1 Module 1',
            'results_file' => $file,
        ])
        ->assertRedirect();

    $exam = Exam::query()->sole();

    expect($exam->questions)->toHaveCount(1)
        ->and($exam->attempts)->toHaveCount(201)
        ->and(ExamAnswer::query()->count())->toBe(201)
        ->and($exam->attempts()->where('student_index_number', 'INDEX-201')->sole()->score)->toEqual('2.00');
});

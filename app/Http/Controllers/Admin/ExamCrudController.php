<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreExamRequest;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use Backpack\ActivityLog\Http\Controllers\Operations\EntryActivityOperation;
use Backpack\ActivityLog\Http\Controllers\Operations\ModelActivityOperation;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExamCrudController extends CrudController
{
    use CreateOperation { store as traitStore; }
    use DeleteOperation;
    use EntryActivityOperation;
    use ListOperation;
    use ModelActivityOperation;
    use ShowOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup(): void
    {
        CRUD::setModel(Exam::class);
        CRUD::setRoute(backpack_url('exam'));
        CRUD::setEntityNameStrings('exam', 'exams');
        CRUD::with(['questions.options']);

        if (backpack_user()->isExaminer()) {
            CRUD::addClause('where', 'created_by', backpack_user()->id);
        }
    }

    public function setupListOperation(): void
    {
        CRUD::column('title');
        CRUD::column('display_mode')->type('text');
        CRUD::column('allow_back_navigation')->type('boolean')->label('Back nav');
        CRUD::column('shuffle_questions')->type('boolean')->label('Shuffle');
        CRUD::column('time_limit_minutes')->type('number')->label('Minutes');
        CRUD::column('expires_at')->type('datetime')->label('Expires at');
        CRUD::addColumn([
            'name' => 'question_count',
            'label' => 'Questions',
            'type' => 'closure',
            'function' => fn (Exam $exam) => $exam->questions()->count(),
        ]);
        CRUD::addColumn([
            'name' => 'attempt_count',
            'label' => 'Attempts',
            'type' => 'closure',
            'function' => fn (Exam $exam) => $exam->attempts()
                ->whereHas('accessLink', fn ($query) => $query->where(function ($nestedQuery) {
                    $nestedQuery->whereNull('meta')
                        ->orWhere('meta->is_preview', false)
                        ->orWhereNull('meta->is_preview');
                }))
                ->count(),
        ]);
        CRUD::column('is_published')->type('boolean')->label('Published');
        CRUD::button('preview')->stack('line')->view('vendor.backpack.crud.buttons.exam_preview');
    }

    public function setupCreateOperation(): void
    {
        CRUD::setValidation(StoreExamRequest::class);
        $this->addFields();
    }

    public function setupUpdateOperation(): void
    {
        CRUD::setValidation(StoreExamRequest::class);
        $this->addFields();
    }

    public function setupShowOperation(): void
    {
        CRUD::column('title');
        CRUD::column('description');
        CRUD::column('instructions');
        CRUD::column('display_mode');
        CRUD::column('allow_back_navigation')->type('boolean');
        CRUD::column('shuffle_questions')->type('boolean');
        CRUD::column('time_limit_minutes');
        CRUD::column('autosave_interval_seconds');
        CRUD::column('expires_at')->type('datetime');
        CRUD::column('show_score_to_student')->type('boolean');
        CRUD::column('show_correct_answers_to_student')->type('boolean');
        CRUD::column('show_index_number_field')->type('boolean');
        CRUD::column('disable_copy_paste')->type('boolean');
        CRUD::addColumn([
            'name' => 'exam_actions',
            'label' => 'Actions',
            'type' => 'closure',
            'escaped' => false,
            'function' => fn (Exam $exam) => '<div class="d-flex flex-wrap gap-2">'
                .'<a class="btn btn-sm btn-outline-primary" href="'.route('admin.exams.access', $exam).'">Manage access</a>'
                .'<a class="btn btn-sm btn-outline-success" href="'.route('admin.exams.results', $exam).'">View results</a>'
                .'<a class="btn btn-sm btn-outline-secondary" href="'.route('admin.exams.csv', $exam).'">Download CSV</a>'
                .'</div>',
        ]);
    }

    public function store(): RedirectResponse
    {
        CRUD::setRequest(CRUD::validateRequest());
        CRUD::getRequest()->request->set('created_by', backpack_user()->id);
        $response = $this->traitStore();
        $this->syncQuestions(CRUD::getCurrentEntry(), CRUD::getRequest());

        return $response;
    }

    public function update(): RedirectResponse
    {
        CRUD::setRequest(CRUD::validateRequest());
        $response = $this->traitUpdate();
        $this->syncQuestions(CRUD::getCurrentEntry(), CRUD::getRequest());

        return $response;
    }

    public function preview(Exam $exam): RedirectResponse
    {
        abort_unless(backpack_user()->can('view', $exam), 403);

        $link = $exam->accessLinks()->create([
            'created_by' => backpack_user()->id,
            'email' => null,
            'max_attempts' => 0,
            'send_email' => false,
            'is_active' => true,
            'expires_at' => now()->addHours(2),
            'meta' => [
                'is_preview' => true,
                'previewed_by_user_id' => backpack_user()->id,
                'previewed_at' => now()->toIso8601String(),
            ],
        ]);

        return redirect()->away($link->examUrl());
    }

    protected function addFields(): void
    {
        $entry = CRUD::getCurrentEntry();
        $entry = $entry instanceof Exam ? $entry : null;

        CRUD::addField([
            'name' => 'exam_wizard_intro',
            'type' => 'custom_html',
            'value' => view('vendor.backpack.crud.fields.exam_builder_intro')->render(),
            'wrapper' => false,
        ]);
        CRUD::addField([
            'name' => 'created_by',
            'type' => 'hidden',
            'value' => $entry?->created_by ?? backpack_user()->id,
        ]);
        CRUD::field('title')->type('text')->wrapper(['class' => 'form-group col-md-12 js-exam-step js-exam-step-1']);
        CRUD::field('description')->type('textarea')->wrapper(['class' => 'form-group col-md-12 js-exam-step js-exam-step-1']);
        CRUD::field('instructions')->type('textarea')->wrapper(['class' => 'form-group col-md-12 js-exam-step js-exam-step-1']);
        CRUD::addField([
            'name' => 'display_mode',
            'label' => 'Question display',
            'type' => 'select_from_array',
            'options' => [
                'all' => 'Show all questions',
                'one_at_a_time' => 'One question at a time',
            ],
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-md-6 js-exam-step js-exam-step-1'],
        ]);
        CRUD::field('allow_back_navigation')
            ->label('Allow students to go back to previous questions')
            ->type('checkbox')
            ->default(true)
            ->wrapper(['class' => 'form-group col-md-6 js-exam-step js-exam-step-1']);
        CRUD::addField([
            'name' => 'shuffle_questions',
            'label' => 'Question order',
            'type' => 'select_from_array',
            'options' => [
                0 => 'Keep fixed order',
                1 => 'Shuffle questions for each attempt',
            ],
            'allows_null' => false,
            'default' => 0,
            'wrapper' => ['class' => 'form-group col-md-6 js-exam-step js-exam-step-1'],
        ]);
        CRUD::field('time_limit_minutes')->type('number')->wrapper(['class' => 'form-group col-md-6 js-exam-step js-exam-step-1']);
        CRUD::field('autosave_interval_seconds')->type('number')->wrapper(['class' => 'form-group col-md-6 js-exam-step js-exam-step-1']);
        CRUD::field('expires_at')->type('datetime')->label('Exam expires at')->wrapper(['class' => 'form-group col-md-6 js-exam-step js-exam-step-1']);
        CRUD::field('show_score_to_student')->type('checkbox')->wrapper(['class' => 'form-group col-md-6 js-exam-step js-exam-step-1']);
        CRUD::field('show_correct_answers_to_student')->type('checkbox')->wrapper(['class' => 'form-group col-md-6 js-exam-step js-exam-step-1']);
        CRUD::field('show_index_number_field')
            ->label('Ask for student index number')
            ->type('checkbox')
            ->wrapper(['class' => 'form-group col-md-6 js-exam-step js-exam-step-1']);
        CRUD::field('disable_copy_paste')->type('checkbox')->wrapper(['class' => 'form-group col-md-6 js-exam-step js-exam-step-1']);
        CRUD::field('is_published')->type('checkbox')->wrapper(['class' => 'form-group col-md-6 js-exam-step js-exam-step-1']);
        CRUD::addField([
            'name' => 'questions',
            'label' => 'Questions',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.fields.exam_questions_builder',
            'entity' => false,
            'value' => old('questions', $entry?->questions?->map(function (Question $question): array {
                return [
                    'id' => $question->id,
                    'type' => $question->type,
                    'prompt' => $question->prompt,
                    'help_text' => $question->help_text,
                    'points' => $question->points,
                    'allows_multiple_selection' => $question->allows_multiple_selection,
                    'accepted_answers' => implode(PHP_EOL, $question->accepted_answers ?? []),
                    'question_options' => $question->options->map(fn (QuestionOption $option): array => [
                        'id' => $option->id,
                        'label' => $option->label,
                        'is_correct' => $option->is_correct,
                    ])->values()->all(),
                ];
            })->values()->all() ?? []),
            'wrapper' => ['class' => 'form-group col-md-12 js-exam-step js-exam-step-2'],
        ]);
        CRUD::addField([
            'name' => 'exam_wizard_outro',
            'type' => 'custom_html',
            'value' => view('vendor.backpack.crud.fields.exam_builder_outro')->render(),
            'wrapper' => false,
        ]);
    }

    protected function syncQuestions(Exam $exam, Request $request): void
    {
        $keptQuestionIds = [];

        foreach ($request->input('questions', []) as $questionIndex => $questionData) {
            $question = $exam->questions()->updateOrCreate(
                ['id' => $questionData['id'] ?? null],
                [
                    'type' => $questionData['type'],
                    'position' => $questionIndex + 1,
                    'prompt' => $questionData['prompt'],
                    'help_text' => $questionData['help_text'] ?: null,
                    'points' => $questionData['points'],
                    'allows_multiple_selection' => $questionData['type'] === Question::TYPE_MULTIPLE_CHOICE
                        ? (bool) ($questionData['allows_multiple_selection'] ?? false)
                        : false,
                    'accepted_answers' => $questionData['type'] === Question::TYPE_FILL_IN
                        ? $questionData['accepted_answers']
                        : [],
                ],
            );

            $keptQuestionIds[] = $question->id;

            if ($question->isFillIn()) {
                $question->options()->delete();

                continue;
            }

            $keptOptionIds = [];

            foreach ($questionData['question_options'] ?? [] as $optionIndex => $optionData) {
                $option = $question->options()->updateOrCreate(
                    ['id' => $optionData['id'] ?? null],
                    [
                        'position' => $optionIndex + 1,
                        'label' => $optionData['label'],
                        'value' => $optionData['label'],
                        'is_correct' => (bool) ($optionData['is_correct'] ?? false),
                    ],
                );

                $keptOptionIds[] = $option->id;
            }

            $question->options()->whereNotIn('id', $keptOptionIds)->delete();
        }

        $exam->questions()->whereNotIn('id', $keptQuestionIds)->delete();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\ExamAttempt;
use Backpack\ActivityLog\Http\Controllers\Operations\EntryActivityOperation;
use Backpack\ActivityLog\Http\Controllers\Operations\ModelActivityOperation;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ExamAttemptCrudController extends CrudController
{
    use DeleteOperation;
    use EntryActivityOperation;
    use ListOperation;
    use ModelActivityOperation;
    use ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(ExamAttempt::class);
        CRUD::setRoute(backpack_url('exam-attempt'));
        CRUD::setEntityNameStrings('exam attempt', 'exam attempts');
        CRUD::with(['exam', 'accessLink']);
        CRUD::addClause('whereHas', 'accessLink', fn ($query) => $query->where(function ($nestedQuery) {
            $nestedQuery->whereNull('meta')
                ->orWhere('meta->is_preview', false)
                ->orWhereNull('meta->is_preview');
        }));

        if (backpack_user()->isExaminer()) {
            CRUD::addClause('whereHas', 'exam', fn ($query) => $query->where('created_by', backpack_user()->id));
        }
    }

    public function setupListOperation(): void
    {
        CRUD::addColumn([
            'name' => 'exam',
            'type' => 'relationship',
            'attribute' => 'title',
        ]);
        CRUD::column('student_name');
        CRUD::column('student_email');
        CRUD::column('student_index_number');
        CRUD::column('status');
        CRUD::column('score');
        CRUD::column('score_percentage');
        CRUD::column('duration_seconds');
        CRUD::column('submitted_at');
    }

    public function setupShowOperation(): void
    {
        CRUD::column('student_name');
        CRUD::column('student_email');
        CRUD::column('student_index_number');
        CRUD::column('status');
        CRUD::column('score');
        CRUD::column('score_percentage');
        CRUD::column('duration_seconds');
        CRUD::column('ip_address');
        CRUD::column('user_agent');
        CRUD::addColumn([
            'name' => 'answers_summary',
            'label' => 'Answers',
            'type' => 'closure',
            'escaped' => false,
            'function' => function (ExamAttempt $attempt): string {
                $attempt->loadMissing('answers.question');

                return $attempt->answers->map(function ($answer): string {
                    $response = $answer->answer_text ?: implode(', ', $answer->selected_option_ids ?? []);

                    return '<strong>'.$answer->question?->prompt.'</strong><br>Response: '.e($response).'<br>Score: '.$answer->score;
                })->implode('<hr>');
            },
        ]);
    }
}

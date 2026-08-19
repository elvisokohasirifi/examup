<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreExamAccessLinkRequest;
use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Notifications\ExamAccessLinkNotification;
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
use Illuminate\Notifications\AnonymousNotifiable;

class ExamAccessLinkCrudController extends CrudController
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
        CRUD::setModel(ExamAccessLink::class);
        CRUD::setRoute(backpack_url('exam-access-link'));
        CRUD::setEntityNameStrings('exam access link', 'exam access links');
        CRUD::with(['exam']);

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
        CRUD::column('email');
        CRUD::column('max_attempts');
        CRUD::column('expires_at');
        CRUD::column('is_active')->type('boolean');
        CRUD::addColumn([
            'name' => 'exam_url',
            'label' => 'Exam link',
            'type' => 'closure',
            'escaped' => false,
            'function' => fn (ExamAccessLink $link) => '<a href="'.$link->examUrl().'" target="_blank">Open link</a>',
        ]);
    }

    public function setupCreateOperation(): void
    {
        CRUD::setValidation(StoreExamAccessLinkRequest::class);
        $this->addFields();
    }

    public function setupUpdateOperation(): void
    {
        CRUD::setValidation(StoreExamAccessLinkRequest::class);
        $this->addFields();
    }

    public function store(): RedirectResponse
    {
        $request = CRUD::validateRequest();
        CRUD::setRequest($request);

        $baseAttributes = [
            'exam_id' => $request->input('exam_id'),
            'created_by' => backpack_user()->id,
            'max_attempts' => $request->input('max_attempts'),
            'expires_at' => $request->input('expires_at'),
            'send_email' => (bool) $request->boolean('send_email'),
            'is_active' => (bool) $request->boolean('is_active'),
        ];

        $links = collect($request->input('emails', []))
            ->when(
                filled($request->input('email')),
                fn ($emails) => $emails->push($request->input('email')),
            )
            ->unique()
            ->values()
            ->map(function (string $email) use ($baseAttributes): ExamAccessLink {
                $link = ExamAccessLink::query()->create([
                    ...$baseAttributes,
                    'email' => $email,
                ]);

                $this->sendInviteIfRequested($link);

                return $link;
            });

        return redirect(backpack_url('exam-access-link'));
    }

    public function update()
    {
        CRUD::setRequest(CRUD::validateRequest());
        $response = $this->traitUpdate();
        $this->sendInviteIfRequested(CRUD::getCurrentEntry());

        return $response;
    }

    protected function addFields(): void
    {
        CRUD::addField([
            'name' => 'exam_id',
            'label' => 'Exam',
            'type' => 'select',
            'entity' => 'exam',
            'attribute' => 'title',
            'model' => Exam::class,
        ]);
        if (CRUD::getCurrentOperation() === 'create') {
            CRUD::addField([
                'name' => 'emails',
                'label' => 'Email addresses',
                'type' => 'textarea',
                'attributes' => [
                    'rows' => 8,
                ],
                'hint' => 'Enter one email address per line. A secure exam link will be created for each address.',
            ]);
        } else {
            CRUD::field('email')->type('email');
        }
        CRUD::field('max_attempts')->type('number');
        CRUD::field('expires_at')->type('datetime');
        CRUD::field('send_email')->type('checkbox');
        CRUD::field('is_active')->type('checkbox');
    }

    protected function sendInviteIfRequested(ExamAccessLink $link): void
    {
        if (! $link->send_email || blank($link->email)) {
            return;
        }

        $link->loadMissing('exam');
        (new AnonymousNotifiable)
            ->route('mail', $link->email)
            ->notify(new ExamAccessLinkNotification($link, $link->examUrl()));

        $link->updateQuietly(['last_sent_at' => now()]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamAccessRequest;
use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Notifications\ExamAccessLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\View\View;

class ExamAccessController extends Controller
{
    public function show(Exam $exam): View
    {
        abort_unless(backpack_user()->can('update', $exam), 403);

        return view('admin.exams.access', [
            'exam' => $exam->loadMissing(['accessLinks' => fn ($query) => $query
                ->where(function ($nestedQuery) {
                    $nestedQuery->whereNull('meta')
                        ->orWhere('meta->is_preview', false)
                        ->orWhereNull('meta->is_preview');
                })
                ->latest()]),
        ]);
    }

    public function store(StoreExamAccessRequest $request, Exam $exam): RedirectResponse
    {
        abort_unless(backpack_user()->can('update', $exam), 403);

        if ($request->input('action') === 'shareable_link') {
            $link = $exam->accessLinks()->create([
                'created_by' => backpack_user()->id,
                'email' => null,
                'max_attempts' => 0,
                'send_email' => false,
                'is_active' => true,
                'expires_at' => $exam->expires_at,
                'meta' => [
                    'shareable' => true,
                    'created_from_exam_access_page' => true,
                ],
            ]);

            return redirect()
                ->route('admin.exams.access', $exam)
                ->with('status', 'Shareable link created: '.$link->examUrl());
        }

        collect($request->input('emails', []))
            ->unique()
            ->each(function (string $email) use ($exam): void {
                $link = $exam->accessLinks()->create([
                    'created_by' => backpack_user()->id,
                    'email' => $email,
                    'max_attempts' => 1,
                    'send_email' => true,
                    'is_active' => true,
                    'expires_at' => $exam->expires_at,
                ]);

                $this->sendInvite($link);
            });

        return redirect()
            ->route('admin.exams.access', $exam)
            ->with('status', 'Invitation links created and emailed successfully.');
    }

    protected function sendInvite(ExamAccessLink $link): void
    {
        if (blank($link->email)) {
            return;
        }

        $link->loadMissing('exam');

        (new AnonymousNotifiable)
            ->route('mail', $link->email)
            ->notify(new ExamAccessLinkNotification($link, $link->examUrl()));

        $link->updateQuietly(['last_sent_at' => now()]);
    }
}

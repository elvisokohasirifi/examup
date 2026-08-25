<div x-data="examActivityMonitor({ disableCopyPaste: @js($exam->disable_copy_paste) })" class="mx-auto max-w-5xl px-4 py-8" wire:poll.10s="refreshAttemptState">
    <div class="rounded-[2rem] border border-white/70 bg-white/85 p-6 shadow-[0_20px_80px_rgba(15,23,42,0.12)] backdrop-blur">
        @if ($isUnavailable)
            <div class="mx-auto flex max-w-xl flex-col items-center py-10 text-center sm:py-16">
                <div class="grid size-20 place-items-center rounded-[1.75rem] bg-amber-100 text-4xl shadow-sm">&#9203;</div>
                <p class="mt-7 text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Exam unavailable</p>
                <h1 class="mt-3 font-serif text-3xl text-slate-900 sm:text-4xl">This link has expired.</h1>
                <p class="mt-4 max-w-md text-sm leading-7 text-slate-600">{{ $unavailableMessage }}</p>
                <div class="mt-8 rounded-2xl bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600">
                    If you believe this is unexpected, please contact the person who sent you the exam link.
                </div>
            </div>
        @else
        <div class="flex flex-col gap-4 border-b border-amber-100 pb-6 md:flex-row md:items-end md:justify-between">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Online Exam</p>
                <h1 class="font-serif text-3xl text-slate-900">{{ $exam->title }}</h1>
                @if ($exam->description)
                    <p class="max-w-3xl text-sm leading-6 text-slate-600">{{ $exam->description }}</p>
                @endif
            </div>

            @if ($attempt)
                <div class="rounded-2xl bg-slate-950 px-4 py-3 text-sm text-white">
                    <div class="font-semibold">Status: {{ str_replace('_', ' ', $attempt->status) }}</div>
                    @if ($this->timeRemaining !== null && ! $attempt->isFinished())
                        <div class="text-amber-300">Time remaining: {{ gmdate('H:i:s', $this->timeRemaining) }}</div>
                    @endif
                </div>
            @endif
        </div>

        @if (! $attempt)
            <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
                <div class="rounded-3xl bg-slate-50 p-6">
                    <h2 class="text-xl font-semibold text-slate-900">Before you begin</h2>
                    <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                        @if ($exam->instructions)
                            <p>{{ $exam->instructions }}</p>
                        @endif
                        <p>Questions: {{ $this->questions->count() }}</p>
                        <p>Display: {{ $exam->display_mode === 'all' ? 'All questions at once' : 'One question at a time' }}</p>
                        @if ($exam->display_mode === 'one_at_a_time' && ! $exam->allow_back_navigation)
                            <p>You cannot go back to a previous question once you continue.</p>
                            <p>Each question must be answered before you can move to the next one.</p>
                        @endif
                        @if ($exam->time_limit_minutes)
                            <p>Time limit: {{ $exam->time_limit_minutes }} minutes</p>
                        @endif
                        @if ($exam->expires_at)
                            <p>Available until: {{ $exam->expires_at->format('M j, Y g:i A') }}</p>
                        @endif
                        <p>Autosave: every response is saved as you type or select.</p>
                    </div>
                </div>

                @if ($resumableAttempt)
                    <div class="space-y-4 rounded-3xl bg-amber-50 p-6">
                        <h2 class="text-xl font-semibold text-slate-900">Resume your saved attempt</h2>
                        <p class="text-sm leading-6 text-slate-600">Enter the email address you used to begin this exam before continuing.</p>
                        <label class="block text-sm font-medium text-slate-700">
                            Email
                            <input type="email" wire:model="candidate.student_email" class="mt-2 w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 outline-none ring-0 focus:border-amber-400" />
                        </label>
                        @error('candidate.student_email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <button type="button" wire:click="resumeAttempt" class="inline-flex rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Resume exam</button>
                        <button type="button" wire:click="startNewAttempt" class="block text-sm font-medium text-slate-700 underline decoration-amber-400 underline-offset-4">Start a new attempt</button>
                    </div>
                @else
                <form wire:submit="startAttempt" class="space-y-4 rounded-3xl bg-amber-50 p-6">
                    <h2 class="text-xl font-semibold text-slate-900">Student details</h2>
                    <label class="block text-sm font-medium text-slate-700">
                        Full name
                        <input type="text" wire:model="candidate.student_name" class="mt-2 w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 outline-none ring-0 focus:border-amber-400" />
                    </label>
                    <label class="block text-sm font-medium text-slate-700">
                        Email
                        <input type="email" wire:model="candidate.student_email" class="mt-2 w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 outline-none ring-0 focus:border-amber-400" />
                    </label>
                    @if ($exam->show_index_number_field)
                        <label class="block text-sm font-medium text-slate-700">
                            Index number
                            <input type="text" wire:model="candidate.student_index_number" class="mt-2 w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 outline-none ring-0 focus:border-amber-400" />
                        </label>
                    @endif
                    @error('candidate.student_name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('candidate.student_email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('candidate.student_index_number') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="inline-flex rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Start exam</button>
                </form>
                @endif
            </div>
        @else
            <div class="mt-8 space-y-6">
                @if ($attempt->isFinished())
                    <div class="rounded-3xl bg-emerald-50 p-6 text-sm text-slate-700">
                        <h2 class="text-2xl font-semibold text-slate-900">Exam submitted</h2>
                        <p class="mt-3">Your responses have been recorded.</p>
                        @if ($exam->show_score_to_student)
                            <p class="mt-2">Score: {{ $attempt->formattedScore() }} / {{ $attempt->formattedMaxScore() }} ({{ $attempt->formattedScorePercentage() }}%)</p>
                        @endif
                        @if ($exam->show_correct_answers_to_student)
                            <div class="mt-4 space-y-4">
                                @foreach ($attempt->answers as $answer)
                                    <div class="rounded-2xl bg-white p-4">
                                        <p class="font-semibold text-slate-900">{{ $answer->question?->prompt }}</p>
                                        @php
                                            $studentAnswer = $answer->question?->isMultipleChoice()
                                                ? $answer->question->options
                                                    ->whereIn('id', $answer->selected_option_ids ?? [])
                                                    ->pluck('label')
                                                    ->implode(', ')
                                                : $answer->answer_text;
                                        @endphp
                                        <p class="mt-2 text-slate-700">Your answer: {{ filled($studentAnswer) ? $studentAnswer : 'No answer submitted' }}</p>
                                        <p class="mt-2">Correct: {{ $answer->is_correct ? 'Yes' : 'No' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-600">
                            Progress: {{ min($currentQuestionIndex + 1, $this->questions->count()) }} / {{ $this->questions->count() }}
                        </div>
                        @if ($exam->display_mode === 'one_at_a_time' && ! $exam->allow_back_navigation)
                            <div class="rounded-full bg-amber-100 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-800">
                                No backtracking
                            </div>
                        @endif
                        <div>
                            <button type="button" wire:click="requestSubmission" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500">Submit exam</button>
                        </div>
                    </div>

                    @error('currentQuestionResponse')
                        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                            {{ $message }}
                        </div>
                    @enderror

                    @foreach ($this->visibleQuestions as $question)
                        @php
                            $displayQuestionNumber = $exam->display_mode === 'one_at_a_time'
                                ? $currentQuestionIndex + 1
                                : $loop->iteration;
                        @endphp
                        <section wire:key="question-{{ $question->id }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-700">Question {{ $displayQuestionNumber }}</p>
                                    <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ $question->prompt }}</h2>
                                    @if ($question->help_text)
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $question->help_text }}</p>
                                    @endif
                                </div>
                                <div class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">{{ $question->formattedPoints() }} pts</div>
                            </div>

                            @if ($question->isMultipleChoice())
                                <div class="mt-6 grid gap-3">
                                    @foreach ($question->options as $option)
                                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:border-amber-300">
                                            @if ($question->allows_multiple_selection)
                                                <input
                                                    type="checkbox"
                                                    wire:model.live="responses.{{ $question->id }}.selected_options.{{ $option->id }}"
                                                    class="mt-1 size-4 border-slate-300 text-amber-500 focus:ring-amber-400"
                                                />
                                            @else
                                                <input
                                                    type="radio"
                                                    wire:model.live="responses.{{ $question->id }}.selected_option_id"
                                                    value="{{ $option->id }}"
                                                    class="mt-1 size-4 border-slate-300 text-amber-500 focus:ring-amber-400"
                                                />
                                            @endif
                                            <span class="text-sm leading-6 text-slate-700">{{ $option->label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <textarea
                                    wire:model.live.debounce.700ms="responses.{{ $question->id }}.answer_text"
                                    rows="5"
                                    class="mt-6 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-800 outline-none focus:border-amber-400"
                                    placeholder="Type your answer here"
                                ></textarea>
                            @endif

                            @if ($exam->display_mode === 'one_at_a_time')
                                <div class="mt-8 flex items-center justify-between gap-3 border-t border-slate-100 pt-5">
                                    <div>
                                        @if ($exam->allow_back_navigation && $currentQuestionIndex > 0)
                                            <button type="button" wire:click="previousQuestion" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900">Previous</button>
                                        @endif
                                    </div>
                                    @if ($currentQuestionIndex < max($this->questions->count() - 1, 0))
                                        <button type="button" wire:click="nextQuestion" class="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Next</button>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endforeach

                    @if ($showSubmitConfirmation)
                        <div class="fixed inset-0 z-50 grid place-items-center bg-slate-950/45 px-4" role="dialog" aria-modal="true" aria-labelledby="submit-confirmation-title">
                            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Final check</p>
                                <h2 id="submit-confirmation-title" class="mt-2 text-2xl font-semibold text-slate-900">Submit your exam?</h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">Your answers will be submitted and you will not be able to edit them afterwards.</p>
                                <div class="mt-6 flex flex-wrap justify-end gap-3">
                                    <button type="button" wire:click="cancelSubmission" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Keep reviewing</button>
                                    <button type="button" wire:click="submitExam" class="rounded-full bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500">Yes, submit exam</button>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endif
        @endif
    </div>
</div>

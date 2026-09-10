@extends(backpack_view('blank'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2 class="mb-2">{{ $attempt->student_name }}'s completed exam</h2>
        <p class="mb-0 text-muted">{{ $exam->title }}</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.exams.results', $exam) }}">Back to results</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h5 class="card-title">Student</h5>
            <dl class="row mb-0">
                <dt class="col-sm-5">Name</dt><dd class="col-sm-7">{{ $attempt->student_name }}</dd>
                <dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $attempt->student_email ?: '-' }}</dd>
                <dt class="col-sm-5">Index number</dt><dd class="col-sm-7">{{ $attempt->student_index_number ?: '-' }}</dd>
            </dl>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h5 class="card-title">Completion</h5>
            <dl class="row mb-0">
                <dt class="col-sm-5">Started</dt><dd class="col-sm-7">{{ $attempt->started_at?->format('M j, Y g:i:s A') ?: '-' }}</dd>
                <dt class="col-sm-5">Completed</dt><dd class="col-sm-7">{{ $attempt->submitted_at?->format('M j, Y g:i:s A') ?: '-' }}</dd>
                <dt class="col-sm-5">Duration</dt><dd class="col-sm-7">{{ $attempt->duration_seconds !== null ? \Carbon\CarbonInterval::seconds($attempt->duration_seconds)->cascade()->forHumans() : '-' }}</dd>
                <dt class="col-sm-5">Status</dt><dd class="col-sm-7 text-capitalize">{{ str_replace('_', ' ', $attempt->status) }}</dd>
                @if ($attempt->automaticSubmissionReasonLabel())
                    <dt class="col-sm-5">Auto-submit reason</dt><dd class="col-sm-7">{{ $attempt->automaticSubmissionReasonLabel() }}</dd>
                @endif
            </dl>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h5 class="card-title">Score and security</h5>
            <dl class="row mb-0">
                <dt class="col-sm-6">Score</dt><dd class="col-sm-6">{{ $attempt->formattedScore() }} / {{ $attempt->formattedMaxScore() }}</dd>
                <dt class="col-sm-6">Percentage</dt><dd class="col-sm-6">{{ $attempt->formattedScorePercentage() }}%</dd>
                <dt class="col-sm-6">Suspicious events</dt><dd class="col-sm-6">{{ $attempt->suspiciousActivities->count() }}</dd>
                <dt class="col-sm-6">IP address</dt><dd class="col-sm-6">{{ $attempt->ip_address ?: '-' }}</dd>
                <dt class="col-sm-6">User agent</dt><dd class="col-sm-6 text-break">{{ $attempt->user_agent ?: '-' }}</dd>
            </dl>
        </div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-title">Honor code</h4>
        @if ($attempt->honor_code_accepted_at)
            <p class="mb-2 text-success">Accepted on {{ $attempt->honor_code_accepted_at->format('M j, Y g:i:s A') }}</p>
        @else
            <p class="mb-2 text-muted">Not confirmed. This may occur when the exam was automatically submitted.</p>
        @endif

        @if ($attempt->honor_code_disclosure)
            <div class="mt-3 rounded border border-warning-subtle bg-warning-subtle p-3">
                <div class="fw-semibold">Confidential integrity disclosure</div>
                <p class="mb-0 mt-2 text-break" style="white-space: pre-line">{{ $attempt->honor_code_disclosure }}</p>
            </div>
        @else
            <p class="mb-0 text-muted">No integrity disclosure was provided.</p>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-title">Suspicious activity</h4>
        @forelse ($attempt->suspiciousActivities as $activity)
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between gap-3">
                    <strong>{{ str_replace('_', ' ', $activity->event_type) }}</strong>
                    <span class="badge bg-{{ $activity->severity === 'high' ? 'danger' : ($activity->severity === 'medium' ? 'warning' : 'secondary') }}">{{ $activity->severity }}</span>
                </div>
                <div class="text-muted small">{{ $activity->created_at?->format('M j, Y g:i:s A') }}</div>
                @if ($activity->details)
                    <div class="mt-2">{{ $activity->details }}</div>
                @endif
                @if (in_array($activity->event_type, ['copy', 'paste'], true) && array_key_exists('clipboard_text', $activity->context ?? []))
                    <div class="mt-2">
                        <div class="small fw-semibold text-muted">Clipboard content</div>
                        <pre class="mb-0 mt-1 rounded bg-light p-2 small text-break text-wrap">{{ $activity->context['clipboard_text'] !== '' ? $activity->context['clipboard_text'] : 'No readable plain text was available.' }}</pre>
                    </div>
                @endif
            </div>
        @empty
            <p class="mb-0 text-muted">No suspicious activity was recorded for this attempt.</p>
        @endforelse
    </div>
</div>

@foreach ($questions as $questionIndex => $question)
    @php
        $answer = $answersByQuestion->get($question->id);
        $selectedOptionIds = collect($answer?->selected_option_ids ?? [])->map(fn ($id) => (string) $id);
        $selectedOptions = $question->options->filter(fn ($option) => $selectedOptionIds->contains((string) $option->id));
        $correctOptions = $question->options->where('is_correct', true);
    @endphp
    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <strong>Question {{ $questionIndex + 1 }}</strong>
            <span class="text-muted">{{ $question->formattedPoints() }} {{ \Illuminate\Support\Str::plural('point', (float) $question->points) }}</span>
        </div>
        <div class="card-body">
            <p class="fs-5">{{ $question->prompt }}</p>
            @if ($question->help_text)
                <p class="text-muted">{{ $question->help_text }}</p>
            @endif

            @if ($question->isMultipleChoice())
                <div>
                    <h6 class="mb-3">Answer choices</h6>
                    <div class="list-group">
                        @foreach ($question->options as $option)
                            @php
                                $isSelected = $selectedOptionIds->contains((string) $option->id);
                                $isCorrect = $option->is_correct;
                            @endphp
                            <div @class([
                                'list-group-item d-flex align-items-center justify-content-between gap-3',
                                'border-success' => $isCorrect,
                                'border-danger' => $isSelected && ! $isCorrect,
                            ])>
                                <div class="d-flex align-items-center gap-3">
                                    <input
                                        class="form-check-input m-0"
                                        type="{{ $question->allows_multiple_selection ? 'checkbox' : 'radio' }}"
                                        @checked($isSelected)
                                        disabled
                                        aria-label="{{ $option->label }}"
                                    >
                                    <span @class(['fw-semibold' => $isSelected || $isCorrect])>{{ $option->label }}</span>
                                </div>
                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                    @if ($isSelected)
                                        <span class="badge bg-{{ $isCorrect ? 'success' : 'danger' }}">Student selected</span>
                                    @endif
                                    @if ($isCorrect)
                                        <span class="badge bg-success">Correct answer</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6>Student answer</h6>
                        <p class="mb-0">{{ $answer?->answer_text ?: 'No answer' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Correct answer</h6>
                        <p class="mb-0">{{ collect($question->accepted_answers)->implode(', ') }}</p>
                    </div>
                </div>
            @endif

            <div class="mt-3">
                <h6>Grading</h6>
                <p class="mb-0">
                    @if ($answer)
                        <span class="badge bg-{{ $answer->is_correct ? 'success' : 'danger' }}">{{ $answer->is_correct ? 'Correct' : 'Incorrect' }}</span>
                        <span class="ms-2">{{ $answer->formattedScore() }} / {{ $question->formattedPoints() }} points</span>
                    @else
                        <span class="badge bg-secondary">Not answered</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
@endforeach
@endsection

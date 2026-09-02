@extends(backpack_view('blank'))

@section('content')
@php
    $averageCompletionMinutes = round($stats['average_completion_time_seconds'] / 60, 1);
    $scorePrecision = fn (float $score): int => fmod($score, 1.0) === 0.0 ? 0 : 1;
@endphp
<div class="row">
    <div class="col-md-12">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="mb-2">{{ $exam->title }} results</h2>
                <p class="mb-0 text-muted">Performance summary for completed attempts.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.exams.regrade', $exam) }}" onsubmit="return confirm('Regrade all completed attempts using the current answer key? Student responses will not change.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning">Regrade completed attempts</button>
                </form>
                <a class="btn btn-outline-primary" href="{{ route('admin.exams.csv', $exam) }}">Download CSV results</a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Bulk allow retakes</h4>
                <p class="text-muted">Paste one email address per line or upload a TXT or CSV file with the email address in the first column. Only candidates with a completed attempt can receive a retake link.</p>
                <form method="POST" action="{{ route('admin.exams.retakes.bulk', $exam) }}" enctype="multipart/form-data" onsubmit="return confirm('Email one-time retake links to every eligible candidate in this list?');">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-7">
                            <label for="retake-emails" class="form-label">Email addresses</label>
                            <textarea id="retake-emails" name="emails" rows="4" class="form-control @error('emails') is-invalid @enderror" placeholder="student@example.com">{{ is_array(old('emails')) ? implode("\n", old('emails')) : old('emails') }}</textarea>
                            @error('emails')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @error('emails.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label for="retake-email-file" class="form-label">TXT or CSV file</label>
                            <input id="retake-email-file" name="email_file" type="file" accept=".txt,.csv,text/plain,text/csv" class="form-control @error('email_file') is-invalid @enderror">
                            @error('email_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-warning">Email retake links</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl">
                <div class="card bg-primary text-white h-100"><div class="card-body">
                    <div class="text-uppercase small opacity-75">Attempts</div>
                    <div class="fs-2 fw-bold">{{ $stats['attempt_count'] }}</div>
                </div></div>
            </div>
            <div class="col-sm-6 col-xl">
                <div class="card bg-success text-white h-100"><div class="card-body">
                    <div class="text-uppercase small opacity-75">Average score</div>
                    <div class="fs-2 fw-bold">{{ \Illuminate\Support\Number::format($stats['average_score'], precision: $scorePrecision((float) $stats['average_score'])) }}</div>
                </div></div>
            </div>
            <div class="col-sm-6 col-xl">
                <div class="card bg-info text-white h-100"><div class="card-body">
                    <div class="text-uppercase small opacity-75">Highest score</div>
                    <div class="fs-2 fw-bold">{{ \Illuminate\Support\Number::format($stats['highest_score'], precision: $scorePrecision((float) $stats['highest_score'])) }}</div>
                </div></div>
            </div>
            <div class="col-sm-6 col-xl">
                <div class="card bg-warning text-dark h-100"><div class="card-body">
                    <div class="text-uppercase small opacity-75">Lowest score</div>
                    <div class="fs-2 fw-bold">{{ \Illuminate\Support\Number::format($stats['lowest_score'], precision: $scorePrecision((float) $stats['lowest_score'])) }}</div>
                </div></div>
            </div>
            <div class="col-sm-6 col-xl">
                <div class="card bg-dark text-white h-100"><div class="card-body">
                    <div class="text-uppercase small opacity-75">Average completion time</div>
                    <div class="fs-2 fw-bold">{{ \Illuminate\Support\Number::format($averageCompletionMinutes, precision: 1) }} <span class="fs-5 fw-normal">minutes</span></div>
                </div></div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <h4 class="card-title mb-0">Student attempts</h4>
                    <form method="GET" action="{{ route('admin.exams.results', $exam) }}" class="d-flex flex-wrap gap-2">
                        <label class="visually-hidden" for="student-name-search">Search by student name</label>
                        <input id="student-name-search" name="student_name" type="search" value="{{ $studentNameSearch }}" class="form-control" placeholder="Search by student name">
                        <button type="submit" class="btn btn-outline-primary">Search</button>
                        @if ($studentNameSearch !== '')
                            <a href="{{ route('admin.exams.results', $exam) }}" class="btn btn-outline-secondary">Clear</a>
                        @endif
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Attempt type</th>
                                <th>Email</th>
                                <th>Index number</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Suspicious activity</th>
                                <th>Submitted</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attempts as $attempt)
                                <tr>
                                    <td>{{ $attempt->student_name }}</td>
                                    <td>
                                        @if (data_get($attempt->accessLink?->meta, 'is_retake'))
                                            <span class="badge bg-primary">Retake</span>
                                        @elseif ($attempt->isSuperseded())
                                            <span class="badge bg-secondary">Original - replaced</span>
                                        @else
                                            <span class="badge bg-light text-dark border">Original</span>
                                        @endif
                                    </td>
                                    <td>{{ $attempt->student_email ?: '-' }}</td>
                                    <td>{{ $attempt->student_index_number ?: '-' }}</td>
                                    <td>{{ str_replace('_', ' ', $attempt->status) }}</td>
                                    <td>{{ $attempt->formattedScore() }} / {{ $attempt->formattedMaxScore() }} ({{ $attempt->formattedScorePercentage() }}%)</td>
                                    <td>{{ $attempt->suspicious_activities_count }}</td>
                                    <td>{{ $attempt->submitted_at?->format('M j, Y g:i A') ?: '-' }}</td>
                                    <td class="text-end">
                                        @if ($attempt->isFinished())
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.exams.attempts.show', [$exam, $attempt]) }}">View completed exam</a>
                                            @if (! $attempt->isSuperseded())
                                                <form method="POST" action="{{ route('admin.exams.attempts.retake', [$exam, $attempt]) }}" class="d-inline" onsubmit="return confirm('Email this student a new one-time retake link? Their current result will remain until they submit the retake.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">Allow retake</button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-muted">Not completed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No exam attempts yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @foreach ($stats['questions'] as $question)
            <div class="card mb-3">
                <div class="card-body">
                    <h5>{{ $question['prompt'] }}</h5>
                    <p>Correct rate: {{ $question['correct_percentage'] }}%</p>

                    @if (isset($question['options']))
                        <ul>
                            @foreach ($question['options'] as $option)
                                <li>{{ $option['label'] }}: {{ $option['selected_count'] }} selections ({{ $option['selected_percentage'] }}%) @if($option['is_correct']) [correct] @endif</li>
                            @endforeach
                        </ul>
                    @else
                        <p>Accepted answers: {{ implode(', ', $question['accepted_answers']) }}</p>
                        <ul>
                            @foreach ($question['top_answers'] as $answer)
                                <li>{{ $answer['answer'] }}: {{ $answer['count'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

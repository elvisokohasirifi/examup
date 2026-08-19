@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="mb-2">{{ $exam->title }} results</h2>
                <p class="mb-0">Attempts: {{ $stats['attempt_count'] }} | Average score: {{ $stats['average_score'] }} | Highest: {{ $stats['highest_score'] }} | Lowest: {{ $stats['lowest_score'] }} | Average completion time: {{ $stats['average_completion_time_seconds'] }} seconds</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('admin.exams.csv', $exam) }}">Download CSV results</a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Student attempts</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Index number</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attempts as $attempt)
                                <tr>
                                    <td>{{ $attempt->student_name }}</td>
                                    <td>{{ $attempt->student_email ?: '-' }}</td>
                                    <td>{{ $attempt->student_index_number ?: '-' }}</td>
                                    <td>{{ str_replace('_', ' ', $attempt->status) }}</td>
                                    <td>{{ $attempt->score }} / {{ $attempt->max_score }} ({{ $attempt->score_percentage }}%)</td>
                                    <td>{{ $attempt->submitted_at?->format('M j, Y g:i A') ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No exam attempts yet.</td>
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

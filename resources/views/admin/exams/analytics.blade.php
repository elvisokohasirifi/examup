@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">{{ $exam->title }} analytics</h2>
        <p>Attempts: {{ $stats['attempt_count'] }} | Average score: {{ $stats['average_score'] }} | Highest: {{ $stats['highest_score'] }} | Lowest: {{ $stats['lowest_score'] }} | Average completion time: {{ $stats['average_completion_time_seconds'] }} seconds</p>
        <p><a href="{{ route('admin.exams.csv', $exam) }}">Download CSV results</a></p>

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

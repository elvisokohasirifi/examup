@extends(backpack_view('blank'))

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">Exam overview</span>
            <small>Everything important, at a glance.</small>
        </h2>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-6 col-xl-3 mb-3"><div class="card border-0 shadow-sm h-100 bg-primary text-white"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="text-white-50 text-uppercase small font-weight-bold">All exams</div><div class="display-6 font-weight-bold">{{ $totalExams }}</div></div><i class="la la-file-alt la-3x text-white-50"></i></div></div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="card border-0 shadow-sm h-100 bg-success text-white"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="text-white-50 text-uppercase small font-weight-bold">Live exams</div><div class="display-6 font-weight-bold">{{ $publishedExams }}</div></div><i class="la la-broadcast-tower la-3x text-white-50"></i></div></div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="card border-0 shadow-sm h-100 bg-warning text-dark"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="text-uppercase small font-weight-bold text-dark-50">In progress</div><div class="display-6 font-weight-bold">{{ $activeAttempts }}</div></div><i class="la la-clock la-3x text-dark-50"></i></div></div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="card border-0 shadow-sm h-100 bg-info text-white"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="text-white-50 text-uppercase small font-weight-bold">Completed</div><div class="display-6 font-weight-bold">{{ $completedAttempts }}</div></div><i class="la la-check-circle la-3x text-white-50"></i></div></div></div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pt-4 px-4">
                    <div><h4 class="mb-1">Recent submissions</h4><p class="text-muted mb-0">Latest completed attempts across your exams.</p></div>
                    <a href="{{ backpack_url('exam') }}" class="btn btn-sm btn-outline-primary">View exams</a>
                </div>
                <div class="card-body px-4 pb-4">
                    @forelse ($recentAttempts as $attempt)
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                            <div><div class="font-weight-bold">{{ $attempt->student_name }}</div><div class="small text-muted">{{ $attempt->exam->title }} &middot; {{ $attempt->submitted_at?->diffForHumans() }}</div></div>
                            <div class="d-flex align-items-center gap-2"><span class="badge badge-light border">{{ $attempt->formattedScore() }} / {{ $attempt->formattedMaxScore() }}</span><a class="btn btn-sm btn-outline-success" href="{{ route('admin.exams.attempts.show', [$attempt->exam, $attempt]) }}">Review</a></div>
                        </div>
                    @empty
                        <div class="rounded bg-light p-4 text-center text-muted">Completed attempts will appear here.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><div class="d-flex align-items-center justify-content-between"><div><p class="text-uppercase small font-weight-bold text-muted mb-1">Security events</p><div class="display-6 font-weight-bold text-danger">{{ $securityEvents }}</div></div><i class="la la-shield-alt la-3x text-danger"></i></div><p class="text-muted mb-4">Logged in the last 30 days across the exams you can manage.</p><a href="{{ backpack_url('exam') }}" class="btn btn-outline-primary w-100">Manage exams</a></div></div></div>
    </div>
@endsection

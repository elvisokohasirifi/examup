@extends(backpack_view('blank'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h2 class="mb-2">Import Microsoft Forms results</h2>
                    <p class="mb-0 text-muted">Create a draft historical exam from a Microsoft Forms Responses CSV export.</p>
                </div>
                <a href="{{ backpack_url('exam') }}" class="btn btn-outline-secondary">Back to exams</a>
            </div>

            <div class="alert alert-info">
                <strong>What will be imported:</strong> candidate details, timestamps, total scores, written responses, and per-question awarded scores.
                Microsoft Forms does not export multiple-choice options or answer keys, so every imported question is kept as a fill-in question and the original grading is preserved.
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.exams.import.microsoft-forms.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="import-title" class="form-label">Exam title</label>
                            <input id="import-title" name="title" type="text" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Advanced Certificate Exams - July 2026" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label for="results-file" class="form-label">Microsoft Forms responses CSV</label>
                            <input id="results-file" name="results_file" type="file" accept=".csv,text/csv,text/plain" class="form-control @error('results_file') is-invalid @enderror" required>
                            <div class="form-text">Use Microsoft Forms: Responses &rarr; Open in Excel or Download a copy, then save/export as CSV.</div>
                            @error('results_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex flex-wrap justify-content-end gap-2">
                            <a href="{{ backpack_url('exam') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" onclick="return confirm('This will create a new draft exam and its historical attempts. Continue?');">Import results</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@include('crud::fields.inc.wrapper_start')
    <div class="card bg-body-tertiary border-0">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="mb-1">Import questions from a text file</h5>
                    <p class="mb-0 text-muted">Upload a `.txt` file to replace the questions currently entered in this form when you save.</p>
                </div>
                <a class="btn btn-outline-primary" href="{{ route('admin.exams.questions.sample') }}">Download sample file</a>
            </div>

            <label class="form-label mt-3" for="questions_import">Question file</label>
            <input id="questions_import" type="file" name="questions_import" accept=".txt,text/plain" class="form-control">
            <div class="form-text">Text files only, up to 1 MB. The imported file replaces the questions in this save.</div>
            @error('questions_import')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
@include('crud::fields.inc.wrapper_end')

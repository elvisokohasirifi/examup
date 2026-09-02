@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-xl-10 mx-auto">
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #102a43, #0f766e);">
            <div class="card-body p-4 p-md-5 text-white">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <p class="text-uppercase small fw-bold mb-2" style="letter-spacing: .12em; color: #99f6e4;">Admin and examiner help</p>
                        <h1 class="h2 mb-2">ExamUp guide</h1>
                        <p class="mb-0 text-white-50">Everything needed to build, deliver, monitor, review, and improve online exams.</p>
                    </div>
                    <a href="{{ backpack_url('exam/create') }}" class="btn btn-light text-primary fw-semibold">Create an exam</a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><div class="text-primary fs-3"><i class="la la-edit"></i></div><h2 class="h5 mt-3">1. Build</h2><p class="mb-0 text-muted">Set the exam rules, then add questions directly in the exam form.</p></div></div></div>
            <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><div class="text-success fs-3"><i class="la la-paper-plane"></i></div><h2 class="h5 mt-3">2. Deliver</h2><p class="mb-0 text-muted">Preview the candidate experience, then share a secure link or email individual invitations.</p></div></div></div>
            <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><div class="text-warning fs-3"><i class="la la-chart-bar"></i></div><h2 class="h5 mt-3">3. Review</h2><p class="mb-0 text-muted">Inspect attempts, activity, answers, scores, and question-level statistics.</p></div></div></div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h4">Quick start</h2>
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Open <a href="{{ backpack_url('exam/create') }}">Exams</a> and select <strong>Add exam</strong>.</li>
                    <li class="mb-2">Complete the exam details step, including how questions display, availability, score visibility, and monitoring rules.</li>
                    <li class="mb-2">Add questions in the Questions step, or import/paste a prepared TXT question file and review the populated cards.</li>
                    <li class="mb-2">Save and publish the exam. Use <strong>Preview Exam</strong> from the exam list to test it safely.</li>
                    <li>Open the exam's access page to create a shareable link or email unique links to candidates.</li>
                </ol>
            </div>
        </div>

        <div class="accordion shadow-sm mb-4" id="help-accordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="help-build-heading"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#help-build">Create and configure an exam</button></h2>
                <div id="help-build" class="accordion-collapse collapse show" data-bs-parent="#help-accordion"><div class="accordion-body">
                    <p>Give the exam a title, description, and instructions. Line breaks entered in descriptions and instructions are shown to candidates.</p>
                    <ul class="mb-0">
                        <li><strong>Show all</strong> displays every assigned question together. <strong>One at a time</strong> adds progress controls.</li>
                        <li>Disable back navigation only when candidates must answer each question before continuing.</li>
                        <li>Set a time limit for each attempt and an expiry date/time for the whole exam. Once the exam expires, it no longer accepts attempts or usable retakes.</li>
                        <li>Enable question shuffling for a new order per candidate. Use <strong>Questions shown per attempt</strong> for a question bank: candidates receive a random subset, and scores use only their assigned questions.</li>
                        <li>Choose whether candidates see scores, correct answers, and the index-number field. Publish only when the exam is ready.</li>
                    </ul>
                </div></div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="help-questions-heading"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help-questions">Add, import, and edit questions</button></h2>
                <div id="help-questions" class="accordion-collapse collapse" data-bs-parent="#help-accordion"><div class="accordion-body">
                    <p>Questions are managed only inside their exam. Use the large <strong>Add question</strong> button below the question cards.</p>
                    <ul class="mb-0">
                        <li><strong>Multiple choice:</strong> add answer options in the question card and mark at least one correct. Enable multiple correct choices when needed.</li>
                        <li><strong>Fill in:</strong> add every accepted answer. Grading ignores casing and extra spacing.</li>
                        <li>Points may be whole numbers or use one decimal place. Each option label is also its saved answer value.</li>
                        <li>Use the TXT sample download in the Questions step as the source format. Upload a TXT file or paste its contents; the form will populate the question cards for review before saving.</li>
                    </ul>
                </div></div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="help-access-heading"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help-access">Preview and share exams</button></h2>
                <div id="help-access" class="accordion-collapse collapse" data-bs-parent="#help-accordion"><div class="accordion-body">
                    <p>Select <strong>Preview Exam</strong> from the exam list to verify the candidate journey without sending it to students.</p>
                    <ul class="mb-0">
                        <li><strong>Shareable link:</strong> anyone with the link can start the exam. Delete unused links, or disable links with attempts to preserve their results.</li>
                        <li><strong>Email individual links:</strong> paste one email address per line. Each recipient gets a different link and their email is prefilled.</li>
                        <li>Exam expiry applies to every link automatically. An expired link shows a friendly unavailable message rather than a candidate error page.</li>
                    </ul>
                </div></div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="help-candidate-heading"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help-candidate">What candidates experience</button></h2>
                <div id="help-candidate" class="accordion-collapse collapse" data-bs-parent="#help-accordion"><div class="accordion-body">
                    <p>Candidates enter their name, email, and optionally index number before starting. Answers are saved as they type or select them, so an unfinished attempt can be resumed through the same link.</p>
                    <ul class="mb-0">
                        <li>One email may complete an individual or shareable-link attempt once, unless you explicitly allow a retake.</li>
                        <li>The countdown is displayed in the browser, while the server enforces the actual expiry.</li>
                        <li>Before a manual submission, candidates must confirm that their answers cannot be changed.</li>
                    </ul>
                </div></div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="help-security-heading"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help-security">Monitoring and security deterrents</button></h2>
                <div id="help-security" class="accordion-collapse collapse" data-bs-parent="#help-accordion"><div class="accordion-body">
                    <p>These settings deter misconduct and record evidence; browsers cannot fully prevent a candidate from using another device or application.</p>
                    <ul class="mb-0">
                        <li><strong>Disable copy and paste</strong> blocks normal form copy/paste events and logs attempts, including available clipboard text.</li>
                        <li>Tab switching, window focus loss, browser-back attempts, fullscreen exits, and copy/paste are recorded as suspicious activity.</li>
                        <li><strong>Require fullscreen</strong> asks supported desktop browsers to enter fullscreen. Leaving fullscreen starts a 15-second auto-submit warning. Unsupported browsers and mobile devices may continue, but leaving the tab/app has the same 15-second deterrent.</li>
                        <li>Use the candidate’s completed-attempt review to inspect activity timestamps and clipboard excerpts.</li>
                    </ul>
                </div></div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="help-results-heading"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help-results">Results, review, CSV, and regrading</button></h2>
                <div id="help-results" class="accordion-collapse collapse" data-bs-parent="#help-accordion"><div class="accordion-body">
                    <p>Open an exam’s results page to view attempts, average/highest/lowest scores, average completion time, and option-selection statistics per question.</p>
                    <ul class="mb-0">
                        <li>Search attempts by candidate name, open <strong>View completed exam</strong>, and see every assigned question, all multiple-choice options, the candidate answer, correct answer, score, time, and suspicious activity.</li>
                        <li>Download CSV results for candidate details, scores, and their answers to every exam question.</li>
                        <li>Use <strong>Regrade completed attempts</strong> after changing answer keys. It recalculates scores from saved student responses without changing those responses.</li>
                        <li><strong>Submitted</strong> means the candidate confirmed manually; <strong>auto submitted</strong> means time or a fullscreen/tab-switch deterrent ended the attempt.</li>
                    </ul>
                </div></div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="help-retakes-heading"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help-retakes">Allow retakes</button></h2>
                <div id="help-retakes" class="accordion-collapse collapse" data-bs-parent="#help-accordion"><div class="accordion-body">
                    <p>From Results, select <strong>Allow retake</strong> for one candidate or use <strong>Bulk allow retakes</strong> to paste addresses or upload a TXT/CSV list. Only candidates with a completed eligible attempt receive a one-time retake link.</p>
                    <p class="mb-0">The existing score remains visible until the retake is submitted. Once submitted, the new result replaces the old result in current statistics and results. An expired exam cannot be retaken; extend its availability first if a retake is intended.</p>
                </div></div>
            </div>
        </div>

        @if (backpack_user()?->isAdmin())
            <div class="card border-primary shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4">Administrator controls</h2>
                    <p class="mb-2">Admins can create and manage examiner accounts from <a href="{{ backpack_url('user') }}">Users</a>. The first application user becomes an admin; additional users are assigned the <strong>admin</strong> or <strong>examiner</strong> role.</p>
                    <p class="mb-0">Use <a href="{{ backpack_url('setting') }}">Settings</a> for application configuration, <a href="{{ backpack_url('activity-log') }}">Activity Logs</a> to audit administrator changes, and <a href="{{ backpack_url('log') }}">Error Logs</a> when troubleshooting application errors.</p>
                </div>
            </div>
        @endif

        <div class="alert alert-info mb-0"><strong>Tip:</strong> Always use Preview Exam and send yourself an individual test invitation before releasing a high-stakes assessment.</div>
    </div>
</div>
@endsection

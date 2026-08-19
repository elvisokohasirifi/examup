@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-md-12">
        <h2 class="mb-3">{{ $exam->title }} access</h2>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Create a shareable link</h4>
                <p class="text-muted">Anyone with this link can access the exam. The exam expiry date still applies.</p>
                <form method="POST" action="{{ route('admin.exams.access.store', $exam) }}">
                    @csrf
                    <input type="hidden" name="action" value="shareable_link">
                    <button type="submit" class="btn btn-primary">Generate shareable link</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Email individual links</h4>
                <p class="text-muted">Enter one email address per line. Each person receives a unique link and their email will be prefilled on the exam page.</p>
                <form method="POST" action="{{ route('admin.exams.access.store', $exam) }}">
                    @csrf
                    <input type="hidden" name="action" value="email_list">
                    <div class="form-group">
                        <label for="emails">Email addresses</label>
                        <textarea id="emails" name="emails" rows="8" class="form-control @error('emails') is-invalid @enderror">{{ old('emails') }}</textarea>
                        @error('emails')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('emails.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-success">Send exam links</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Existing links</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Email</th>
                                <th>Link</th>
                                <th>Sent</th>
                                <th>Expires</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($exam->accessLinks as $link)
                                <tr>
                                    <td>{{ data_get($link->meta, 'shareable') ? 'Shareable' : 'Individual' }}</td>
                                    <td>{{ $link->email ?: 'Everyone with link' }}</td>
                                    <td><a href="{{ $link->examUrl() }}" target="_blank" rel="noopener noreferrer">Open link</a></td>
                                    <td>{{ $link->last_sent_at?->format('M j, Y g:i A') ?: '-' }}</td>
                                    <td>{{ $link->expires_at?->format('M j, Y g:i A') ?: '-' }}</td>
                                    <td>{{ $link->is_active ? 'Active' : 'Disabled' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No links created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

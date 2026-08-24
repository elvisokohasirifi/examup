@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-md-12">
        <h2 class="mb-3">{{ $exam->title }} access</h2>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if (session('shareable_link_url'))
            <div class="card mb-4 border-success">
                <div class="card-body">
                    <h4 class="card-title mb-2">Shareable link ready</h4>
                    <p class="text-muted">Copy and share this exam link.</p>
                    <div class="input-group">
                        <input type="text" class="form-control" readonly value="{{ session('shareable_link_url') }}" data-shareable-link-input>
                        <button type="button" class="btn btn-success" data-copy-link-button data-copy-text="{{ session('shareable_link_url') }}">Copy link</button>
                    </div>
                    <div class="small text-muted mt-2" data-copy-link-feedback></div>
                </div>
            </div>
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
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($exam->accessLinks as $link)
                                <tr>
                                    <td>{{ data_get($link->meta, 'shareable') ? 'Shareable' : 'Individual' }}</td>
                                    <td>{{ $link->email ?: 'Everyone with link' }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ $link->examUrl() }}" target="_blank" rel="noopener noreferrer">Open link</a>
                                            @if (data_get($link->meta, 'shareable'))
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-link-button data-copy-text="{{ $link->examUrl() }}">Copy</button>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $link->last_sent_at?->format('M j, Y g:i A') ?: '-' }}</td>
                                    <td>{{ $link->expires_at?->format('M j, Y g:i A') ?: '-' }}</td>
                                    <td>
                                        @if ($exam->hasExpired() || $link->hasExpired())
                                            <span class="badge bg-warning text-dark">Expired</span>
                                        @elseif ($link->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Disabled</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if (data_get($link->meta, 'shareable'))
                                            <form method="POST" action="{{ route('admin.exams.access.destroy', [$exam, $link]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No links created yet.</td>
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

@push('after_scripts')
<script>
    document.querySelectorAll('[data-copy-link-button]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.getAttribute('data-copy-text') ?? '';

            if (!text) {
                return;
            }

            try {
                await navigator.clipboard.writeText(text);

                const feedback = document.querySelector('[data-copy-link-feedback]');
                if (feedback) {
                    feedback.textContent = 'Link copied to clipboard.';
                }

                const originalText = button.textContent;
                button.textContent = 'Copied';

                window.setTimeout(() => {
                    button.textContent = originalText;
                }, 1500);
            } catch (error) {
                const input = document.querySelector('[data-shareable-link-input]');
                if (input instanceof HTMLInputElement) {
                    input.focus();
                    input.select();
                }
            }
        });
    });
</script>
@endpush

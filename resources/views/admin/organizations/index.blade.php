@extends(backpack_view('blank'))

@section('header')
    <section class="container-fluid">
        <h2><span class="text-capitalize">Organisations</span><small>Registered ExamUP workspaces.</small></h2>
    </section>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h4 class="mb-1">Registered organisations</h4>
            <p class="text-muted mb-0">Freeze an organisation to suspend staff access and new exam responses without deleting its data.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Organisation</th>
                        <th>Users</th>
                        <th>Departments</th>
                        <th>Exams</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organizations as $organization)
                        <tr>
                            <td><strong>{{ $organization->name }}</strong><div class="small text-muted">{{ $organization->slug }}</div></td>
                            <td>{{ $organization->users_count }}</td>
                            <td>{{ $organization->departments_count }}</td>
                            <td>{{ $organization->exams_count }}</td>
                            <td>{{ $organization->created_at?->format('M j, Y') }}</td>
                            <td><span class="badge {{ $organization->is_frozen ? 'bg-danger' : 'bg-success' }}">{{ $organization->is_frozen ? 'Frozen' : 'Active' }}</span></td>
                            <td class="text-end">
                                <form method="POST" action="{{ $organization->is_frozen ? route('admin.organizations.unfreeze', $organization) : route('admin.organizations.freeze', $organization) }}">
                                    @csrf
                                    <button class="btn btn-sm {{ $organization->is_frozen ? 'btn-outline-success' : 'btn-outline-danger' }}" type="submit">{{ $organization->is_frozen ? 'Unfreeze' : 'Freeze' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-5 text-center text-muted">No organisations have registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($organizations->hasPages())
            <div class="card-footer bg-transparent">{{ $organizations->links() }}</div>
        @endif
    </div>
@endsection

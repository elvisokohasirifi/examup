@extends(backpack_view('blank'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div><h2 class="mb-1">Assign users to a department</h2><p class="text-muted mb-0">Assign selected admins or examiners in bulk. Organisation administrators may also make them department administrators.</p></div>
    <a class="btn btn-outline-secondary" href="{{ backpack_url('user') }}">Back to users</a>
</div>
<form method="POST" action="{{ route('admin.users.assign-department.store') }}">
    @csrf
    <div class="card mb-4"><div class="card-body row g-3 align-items-end">
        <div class="col-md-7"><label class="form-label">Department</label><select name="department_id" class="form-select" required><option value="">Select a department</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
        <div class="col-md-5"><label class="form-check"><input type="checkbox" class="form-check-input" name="make_department_admins" value="1"> <span class="form-check-label">Make selected users department administrators</span></label></div>
    </div></div>
    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table align-middle"><thead><tr><th></th><th>Name</th><th>Email</th><th>Role</th><th>Current department</th></tr></thead><tbody>@foreach($users as $user)<tr><td><input class="form-check-input" type="checkbox" name="user_ids[]" value="{{ $user->id }}"></td><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role }}</td><td>{{ $user->department?->name ?: '-' }}</td></tr>@endforeach</tbody></table></div><button class="btn btn-primary">Assign selected users</button></div></div>
    @foreach($errors->all() as $error)<p class="text-danger mt-2">{{ $error }}</p>@endforeach
</form>
@endsection

<div class="staff-result-summary"><strong>{{ number_format($staff->total()) }}</strong> matching staff {{ Str::plural('record', $staff->total()) }}</div>
<div class="table-responsive"><table class="table table-bordered table-hover">
    <thead><tr><th>Staff ID</th><th>Name</th><th>Phone</th><th>Department</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($staff as $member)
        <tr>
            <td>{{ $member->staff_id ?: 'Unverified' }}</td>
            <td>{{ $member->full_name }}</td>
            <td>{{ $member->phone }}</td>
            <td>{{ $member->department }}</td>
            <td><span class="badge badge-{{ $member->is_active ? 'success' : 'secondary' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="text-right"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.staff.show', $member) }}"><i class="fas fa-eye"></i></a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center py-5"><i class="fas fa-search text-muted fa-2x mb-2"></i><div class="text-muted">No staff records match your search.</div></td></tr>
    @endforelse
    </tbody>
</table></div>
<div class="staff-pagination">{{ $staff->links() }}</div>

@php
    $pendingRequestDeletions = \App\Models\BenefitRequestDeletionRequest::query()
        ->where('status', 'pending')->with(['benefitRequest.staff', 'requester'])->latest()->get();
    $pendingBenefitDeletions = \App\Models\BenefitDeletionRequest::query()
        ->where('status', 'pending')->with(['benefit.staff', 'requester'])->latest()->get();
    $pendingApprovalCount = $pendingRequestDeletions->count() + $pendingBenefitDeletions->count();
@endphp
@if($pendingApprovalCount > 0)
<div class="card card-warning" role="region" aria-label="Benefit deletion approvals">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-bell mr-1"></i> Benefit Deletions Awaiting Second Admin <span class="badge badge-light ml-1">{{ $pendingApprovalCount }}</span></h3></div>
    <div class="card-body">
        @foreach($pendingRequestDeletions as $deletion)
            <div class="approval-request">
                <div><strong>Request: {{ $deletion->benefitRequest?->staff?->full_name }} — {{ $deletion->benefitRequest?->subject }}</strong><small class="d-block text-muted">Requested by {{ $deletion->requester?->name }}: {{ $deletion->reason }}</small></div>
                @if($deletion->requested_by === auth()->id())
                    <span class="badge badge-secondary">Waiting for another admin</span>
                @else
                    <div class="approval-actions">
                        <form method="post" action="{{ route('admin.benefit-requests.deletion-requests.approve', $deletion) }}">@csrf<div class="input-group input-group-sm"><input name="password" type="password" class="form-control" placeholder="Your password" aria-label="Password to approve request deletion" required><div class="input-group-append"><button class="btn btn-danger">Approve Delete</button></div></div></form>
                        <form method="post" action="{{ route('admin.benefit-requests.deletion-requests.reject', $deletion) }}">@csrf<div class="input-group input-group-sm"><input name="password" type="password" class="form-control" placeholder="Your password" aria-label="Password to reject request deletion" required><div class="input-group-append"><button class="btn btn-outline-secondary">Reject</button></div></div></form>
                    </div>
                @endif
            </div>
        @endforeach
        @foreach($pendingBenefitDeletions as $deletion)
            <div class="approval-request">
                <div><strong>Benefit: {{ $deletion->benefit?->staff?->full_name }} — {{ $deletion->benefit?->title }}</strong><small class="d-block text-muted">Requested by {{ $deletion->requester?->name }}: {{ $deletion->reason }}</small></div>
                @if($deletion->requested_by === auth()->id())
                    <span class="badge badge-secondary">Waiting for another admin</span>
                @else
                    <div class="approval-actions">
                        <form method="post" action="{{ route('admin.benefits.deletion-requests.approve', $deletion) }}">@csrf<div class="input-group input-group-sm"><input name="password" type="password" class="form-control" placeholder="Your password" aria-label="Password to approve benefit deletion" required><div class="input-group-append"><button class="btn btn-danger">Approve Delete</button></div></div></form>
                        <form method="post" action="{{ route('admin.benefits.deletion-requests.reject', $deletion) }}">@csrf<input type="hidden" name="review_notes" value="Rejected by reviewing administrator"><div class="input-group input-group-sm"><input name="password" type="password" class="form-control" placeholder="Your password" aria-label="Password to reject benefit deletion" required><div class="input-group-append"><button class="btn btn-outline-secondary">Reject</button></div></div></form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif

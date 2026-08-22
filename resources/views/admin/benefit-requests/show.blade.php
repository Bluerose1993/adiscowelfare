@extends('layouts.app', ['title' => 'Benefit Request'])

@section('content')
<div class="mb-3"><button class="btn btn-outline-danger" data-toggle="collapse" data-target="#deleteBenefitRequest"><i class="fas fa-trash"></i> Delete Request</button></div><div class="collapse" id="deleteBenefitRequest"><div class="card card-danger"><form method="post" action="{{ route('admin.benefit-requests.deletion-request', $requestRecord) }}" class="card-body deletion-request-form">@csrf<div><strong>Delete this benefit request</strong><small class="d-block text-muted">A second admin must approve in Production mode.</small></div><input name="reason" class="form-control" placeholder="Reason for deletion" required><input name="password" type="password" class="form-control" placeholder="Your password" required><button class="btn btn-danger">Request Delete</button></form></div></div>
<div class="row">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h4>{{ $requestRecord->subject }}</h4>
            <p>{{ $requestRecord->description }}</p>
            <dl class="row">
                <dt class="col-sm-4">Staff</dt><dd class="col-sm-8">{{ $requestRecord->staff?->full_name }}</dd>
                <dt class="col-sm-4">Type</dt><dd class="col-sm-8">{{ $requestRecord->benefitType?->name }}</dd>
                <dt class="col-sm-4">Requested Amount</dt><dd class="col-sm-8">{{ $requestRecord->requested_amount ? number_format($requestRecord->requested_amount, 2) : '-' }}</dd>
                <dt class="col-sm-4">Approved Amount</dt><dd class="col-sm-8"><strong>{{ $requestRecord->approved_amount ? number_format($requestRecord->approved_amount, 2) : 'Not approved yet' }}</strong></dd>
                <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><span class="badge badge-info">{{ str_replace('_', ' ', $requestRecord->status) }}</span></dd>
                <dt class="col-sm-4">Resulting Benefit</dt><dd class="col-sm-8">{{ $requestRecord->resultingBenefit ? $requestRecord->resultingBenefit->title : '-' }}</dd>
            </dl>
            <hr>
            <h5><i class="fas fa-paperclip"></i> Attached Proof</h5>
            @forelse($requestRecord->attachments as $attachment)
                @php
                    $attachmentUrl = Storage::disk('public')->url($attachment->path);
                    $isImage = str_starts_with($attachment->mime_type, 'image/');
                    $isPdf = $attachment->mime_type === 'application/pdf' || strtolower(pathinfo($attachment->original_filename, PATHINFO_EXTENSION)) === 'pdf';
                    $extension = strtoupper(pathinfo($attachment->original_filename, PATHINFO_EXTENSION));
                @endphp
                <div class="attachment-preview-card mb-3">
                    <a class="attachment-thumbnail" href="{{ $attachmentUrl }}" target="_blank" rel="noopener" aria-label="Preview {{ $attachment->original_filename }}">
                        @if($isImage)
                            <img src="{{ $attachmentUrl }}" alt="Preview of {{ $attachment->original_filename }}" loading="lazy">
                        @elseif($isPdf)
                            <iframe src="{{ $attachmentUrl }}#toolbar=0&navpanes=0&scrollbar=0" title="Preview of {{ $attachment->original_filename }}" loading="lazy" tabindex="-1"></iframe>
                            <span class="attachment-preview-overlay"><i class="fas fa-search-plus"></i></span>
                        @else
                            <span class="attachment-document-icon"><i class="fas fa-file-word"></i><strong>{{ $extension ?: 'FILE' }}</strong><small>Open to preview</small></span>
                        @endif
                    </a>
                    <div class="attachment-preview-details"><div><strong>{{ $attachment->original_filename }}</strong><div class="small text-muted">{{ $attachment->mime_type }} · {{ number_format($attachment->size / 1024, 1) }} KB</div></div>
                    <a class="btn btn-outline-primary" href="{{ $attachmentUrl }}" target="_blank" rel="noopener"><i class="fas fa-eye"></i> View File</a></div>
                </div>
            @empty
                <div class="alert alert-secondary mb-0">No proof file is attached to this historical request.</div>
            @endforelse
        </div></div>
    </div>
    <div class="col-lg-5">
        <form method="post" action="{{ route('admin.benefit-requests.review', $requestRecord) }}">
            @csrf
            <div class="card card-primary"><div class="card-header"><h3 class="card-title">Review</h3></div><div class="card-body">
                <div class="form-group"><label>Status</label><select name="status" class="form-control">@foreach(['under_review','approved','returned','rejected','cancelled','paid'] as $item)<option value="{{ $item }}">{{ $item === 'returned' ? 'Return for adjustment' : str_replace('_', ' ', ucfirst($item)) }}</option>@endforeach</select></div>
                <div class="form-group"><label>Approved Amount</label><input name="approved_amount" type="number" step="0.01" min="0.01" class="form-control" value="{{ old('approved_amount', $requestRecord->approved_amount ?? $requestRecord->requested_amount) }}"><small class="text-muted">Adjust this before approval. The approved value becomes the benefit amount shown to staff.</small></div>
                <div class="form-group"><label>Review Notes</label><textarea name="review_notes" class="form-control" rows="3">{{ old('review_notes') }}</textarea></div>
            </div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-check"></i> Save Review</button></div></div>
        </form>
    </div>
</div>
@endsection

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenefitRequestDeletionRequest extends Model
{
    protected $fillable = ['benefit_request_id', 'requested_by', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_notes'];
    protected function casts(): array { return ['reviewed_at' => 'datetime']; }
    public function benefitRequest(): BelongsTo { return $this->belongsTo(BenefitRequest::class)->withDefault(); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
}

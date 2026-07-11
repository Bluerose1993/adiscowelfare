<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuesPaymentDeletionRequest extends Model
{
    protected $fillable = ['dues_payment_id', 'requested_by', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_notes'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function payment(): BelongsTo { return $this->belongsTo(DuesPayment::class, 'dues_payment_id')->withTrashed(); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenefitDeletionRequest extends Model
{
    protected $fillable = ['benefit_id', 'requested_by', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_notes'];

    protected function casts(): array { return ['reviewed_at' => 'datetime']; }

    public function benefit(): BelongsTo { return $this->belongsTo(Benefit::class)->withTrashed(); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}

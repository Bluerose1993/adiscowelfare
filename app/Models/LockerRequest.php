<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LockerRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = ['staff_id', 'preferred_locker_number', 'notes', 'status', 'assigned_locker_number', 'reviewed_by', 'reviewed_at', 'review_notes'];
    protected function casts(): array { return ['reviewed_at' => 'datetime']; }
    public function staff(): BelongsTo { return $this->belongsTo(Staff::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}

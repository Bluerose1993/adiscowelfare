<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuesPaymentReceipt extends Model
{
    use SoftDeletes;

    protected $fillable = ['staff_id', 'amount', 'starting_year', 'starting_month', 'payment_date', 'payment_method', 'reference_number', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date', 'starting_year' => 'integer', 'starting_month' => 'integer'];
    }

    public function staff(): BelongsTo { return $this->belongsTo(Staff::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function allocations(): HasMany { return $this->hasMany(DuesPayment::class, 'receipt_id'); }
    public function deletionRequests(): HasMany { return $this->hasMany(DuesPaymentDeletionRequest::class, 'receipt_id'); }
}

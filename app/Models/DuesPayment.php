<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuesPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'receipt_id',
        'payment_year',
        'payment_month',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'recorded_by',
        'updated_by',
        'deleted_by',
        'deleted_reason',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'payment_year' => 'integer',
            'payment_month' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (DuesPayment $payment) {
            if ($payment->receipt_id) {
                return;
            }
            $receipt = DuesPaymentReceipt::query()->create([
                'staff_id' => $payment->staff_id,
                'amount' => $payment->amount,
                'starting_year' => $payment->payment_year,
                'starting_month' => $payment->payment_month,
                'payment_date' => $payment->payment_date,
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'notes' => $payment->notes,
                'recorded_by' => $payment->recorded_by,
            ]);
            $payment->forceFill(['receipt_id' => $receipt->id])->saveQuietly();
        });
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(DuesPaymentReceipt::class, 'receipt_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletionRequests(): HasMany
    {
        return $this->hasMany(DuesPaymentDeletionRequest::class);
    }
}

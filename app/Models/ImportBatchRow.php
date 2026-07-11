<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'staff_id',
        'full_name',
        'monthly_amounts',
        'reported_total',
        'matched_staff_id',
        'status',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amounts' => 'array',
            'reported_total' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function matchedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'matched_staff_id');
    }
}

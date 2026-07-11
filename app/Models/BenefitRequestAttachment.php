<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenefitRequestAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'benefit_request_id',
        'original_filename',
        'stored_filename',
        'path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function benefitRequest(): BelongsTo
    {
        return $this->belongsTo(BenefitRequest::class);
    }
}

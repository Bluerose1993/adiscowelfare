<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BenefitType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'default_amount',
        'requires_approval',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function benefits(): HasMany
    {
        return $this->hasMany(Benefit::class);
    }
}

<?php
// app/Models/Visit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'pet_id',
        'visit_date',
        'visit_number',
        'sequence',
        'chief_complaint',
        'examination_notes',
        'diagnosis',
        'treatment_plan',
        'prescription',
        'follow_up_date',
        'visit_type',
        'status',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_status',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'follow_up_date' => 'date',
        'visit_number' => 'integer',
        'sequence' => 'integer',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getFormattedVisitNumberAttribute(): string
    {
        return 'V' . str_pad($this->visit_number, 4, '0', STR_PAD_LEFT);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'completed';
    }
}

// =============================================================
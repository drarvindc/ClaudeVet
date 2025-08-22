<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'pet_id', 'visit_date', 'visit_seq', 'status', 'source',
        'reason', 'chief_complaint', 'examination_notes', 'diagnosis',
        'treatment', 'prescription', 'remarks', 'next_visit',
        'created_by', 'doctor_id', 'weight', 'temperature', 'vitals',
        'is_sample_data'
    ];

    protected $casts = [
        'visit_date' => 'date',
        'next_visit' => 'date',
        'weight' => 'decimal:2',
        'temperature' => 'decimal:1',
        'vitals' => 'json',
        'is_sample_data' => 'boolean',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
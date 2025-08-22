<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pet extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'owner_id',
        'name',
        'species_id',
        'breed_id',
        'gender',
        'age_years',
        'age_months',
        'color',
        'weight',
        'distinguishing_marks',
        'microchip_number',
        'sterilization_status',
        'status',
    ];

    protected $casts = [
        'age_years' => 'integer',
        'age_months' => 'integer',
        'weight' => 'decimal:2',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    // Helper methods
    public function getFormattedAgeAttribute(): string
    {
        if ($this->age_years && $this->age_months) {
            return $this->age_years . 'y ' . $this->age_months . 'm';
        } elseif ($this->age_years) {
            return $this->age_years . ' years';
        } elseif ($this->age_months) {
            return $this->age_months . ' months';
        }
        return 'Unknown';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? "Pet {$this->unique_id}";
    }

    public function isProvisional(): bool
    {
        // Your database doesn't have provisional status, so check if it's a basic record
        return empty($this->name) || $this->name === 'Unknown';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
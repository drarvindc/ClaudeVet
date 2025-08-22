<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'address',
        'locality',
        'city',
        'pincode',
        'status',
        'created_via',
        'is_sample_data'
    ];

    protected $casts = [
        'is_sample_data' => 'boolean',
    ];

    /**
     * Get the owner's mobile numbers
     */
    public function mobiles(): HasMany
    {
        return $this->hasMany(OwnerMobile::class);
    }

    /**
     * Get the owner's primary mobile
     */
    public function primaryMobile()
    {
        return $this->hasOne(OwnerMobile::class)->where('is_primary', true);
    }

    /**
     * Get the owner's pets
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    /**
     * Get all visits for all pets
     */
    public function visits()
    {
        return $this->hasManyThrough(Visit::class, Pet::class);
    }

    /**
     * Scope for provisional records
     */
    public function scopeProvisional($query)
    {
        return $query->where('status', 'provisional');
    }

    /**
     * Scope for active records
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for sample data
     */
    public function scopeSampleData($query)
    {
        return $query->where('is_sample_data', true);
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->locality,
            $this->city,
            $this->pincode
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get display mobile (primary or first available)
     */
    public function getDisplayMobileAttribute()
    {
        $primary = $this->mobiles->where('is_primary', true)->first();
        if ($primary) {
            return $primary->mobile;
        }
        
        return $this->mobiles->first()?->mobile;
    }

    /**
     * Check if owner has complete information
     */
    public function isComplete(): bool
    {
        return $this->status === 'active' 
            && !empty($this->name) 
            && $this->name !== 'Provisional Owner'
            && $this->mobiles->isNotEmpty();
    }
}
<?php

// app/Models/Owner.php - Updated with completion tracking

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'is_complete',
    ];

    protected $casts = [
        'is_complete' => 'boolean',
    ];

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    public function mobiles(): HasMany
    {
        return $this->hasMany(OwnerMobile::class);
    }

    public function primaryMobile(): HasMany
    {
        return $this->hasMany(OwnerMobile::class)->where('is_primary', true);
    }

    // Helper methods
    public function getFullNameAttribute(): string
    {
        return $this->name ?: 'Unknown Owner';
    }

    public function getPrimaryMobileNumberAttribute(): ?string
    {
        return $this->primaryMobile()->first()?->mobile;
    }

    public function getAllMobileNumbersAttribute(): array
    {
        return $this->mobiles()->pluck('mobile')->toArray();
    }

    public function isProvisional(): bool
    {
        return $this->created_via === 'provisional';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isComplete(): bool
    {
        return $this->is_complete === true;
    }

    // Static methods for search
    public static function findByMobile(string $mobile): ?self
    {
        $normalizedMobile = OwnerMobile::normalizeMobile($mobile);
        
        return self::whereHas('mobiles', function ($query) use ($normalizedMobile) {
            $query->where('mobile', $normalizedMobile);
        })->first();
    }

    public static function findPetsByMobile(string $mobile)
    {
        $normalizedMobile = OwnerMobile::normalizeMobile($mobile);
        
        return Pet::whereHas('owner.mobiles', function ($query) use ($normalizedMobile) {
            $query->where('mobile', $normalizedMobile);
        })->with(['owner', 'species', 'breed'])->get();
    }
}

// =============================================================
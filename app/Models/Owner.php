<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Owner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'address',
        'locality',
        'city',
        'pincode',
        'status',
        'created_via',
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
        return $this->primaryMobile()->first()?->mobile_e164;
    }

    public function getAllMobileNumbersAttribute(): array
    {
        return $this->mobiles()->pluck('mobile_e164')->toArray();
    }

    public function isProvisional(): bool
    {
        return $this->created_via === 'provisional';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // Static methods for search
    public static function findByMobile(string $mobile): ?self
    {
        return self::whereHas('mobiles', function ($query) use ($mobile) {
            $query->where('mobile_e164', 'LIKE', '%' . $mobile);
        })->first();
    }
}
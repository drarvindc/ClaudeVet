<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerMobile extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'mobile',
        'mobile_e164',
        'is_primary',
        'is_whatsapp',
        'is_verified',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_whatsapp' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    // Helper methods
    public function getFormattedMobileAttribute(): string
    {
        // Format +919876543210 to +91 98765 43210
        $mobile = $this->mobile_e164;
        if (str_starts_with($mobile, '+91')) {
            return '+91 ' . substr($mobile, 3, 5) . ' ' . substr($mobile, 8);
        }
        return $mobile;
    }

    public static function normalizeMobile(string $mobile): string
    {
        // Remove all non-digits
        $mobile = preg_replace('/\D/', '', $mobile);
        
        // For Indian mobile numbers, just return the 10-digit number
        if (strlen($mobile) === 10 && in_array(substr($mobile, 0, 1), ['6', '7', '8', '9'])) {
            return $mobile;
        }
        
        // If it's longer than 10 digits, take the last 10 digits (removes country codes)
        if (strlen($mobile) > 10) {
            return substr($mobile, -10);
        }
        
        return $mobile;
    }
}
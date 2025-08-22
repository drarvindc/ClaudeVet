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
        
        // Add +91 if it's a 10-digit Indian number
        if (strlen($mobile) === 10 && str_starts_with($mobile, ['6', '7', '8', '9'])) {
            return '+91' . $mobile;
        }
        
        // Add + if it starts with country code
        if (strlen($mobile) > 10 && !str_starts_with($mobile, '+')) {
            return '+' . $mobile;
        }
        
        return $mobile;
    }
}
<?php
// app/Models/OwnerMobile.php - Updated for simplified mobile handling

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
        // Format 9876543210 to 98765 43210
        $mobile = $this->mobile;
        if (strlen($mobile) === 10) {
            return substr($mobile, 0, 5) . ' ' . substr($mobile, 5);
        }
        return $mobile;
    }

    public static function normalizeMobile(string $mobile): string
    {
        // Remove all non-digits
        $mobile = preg_replace('/\D/', '', $mobile);
        
        // For Indian mobile numbers, ensure it's exactly 10 digits
        if (strlen($mobile) === 10 && in_array(substr($mobile, 0, 1), ['6', '7', '8', '9'])) {
            return $mobile;
        }
        
        // If it's longer than 10 digits, take the last 10 digits (removes country codes)
        if (strlen($mobile) > 10) {
            return substr($mobile, -10);
        }
        
        return $mobile;
    }

    public static function validateMobile(string $mobile): bool
{
    $normalized = self::normalizeMobile($mobile);
    
    // Must be exactly 10 digits and start with 6,7,8,9
    return strlen($normalized) === 10 && 
           ctype_digit($normalized) && 
           in_array(substr($normalized, 0, 1), ['6', '7', '8', '9']);
}
}

// =============================================================
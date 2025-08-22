<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerMobile extends Model
{
    protected $fillable = [
        'owner_id', 'mobile', 'mobile_e164', 'is_primary', 'is_whatsapp'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_whatsapp' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}
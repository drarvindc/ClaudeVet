<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'patient_unique_id', 'pet_id', 'visit_id', 'type',
        'subtype', 'path', 'filename', 'original_name', 'source',
        'mime_type', 'size_bytes', 'captured_at', 'checksum_sha1',
        'metadata'
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
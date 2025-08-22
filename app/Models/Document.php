<?php
// app/Models/Document.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_unique_id',
        'pet_id',
        'visit_id',
        'type',
        'subtype',
        'path',
        'filename',
        'original_filename',
        'source',
        'ref_id',
        'seq',
        'mime',
        'size_bytes',
        'captured_at',
        'checksum_sha1',
        'created_by',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'size_bytes' => 'integer',
        'seq' => 'integer',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->path);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size_bytes) return 'Unknown';
        
        $bytes = $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return in_array($this->type, ['photo', 'xray', 'usg']) || 
               str_starts_with($this->mime ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf' || 
               str_ends_with($this->filename, '.pdf');
    }
}
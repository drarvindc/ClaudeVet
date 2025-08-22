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
        'visit_id',
        'pet_id',
        'type',
        'filename',
        'filesize',
        'mime',
        'note',
        'checksum_sha1',
    ];

    protected $casts = [
        'filesize' => 'integer',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->filesize) return 'Unknown';
        
        $bytes = $this->filesize;
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
<?php

// app/Models/Pet.php - Updated with completion and duplicate tracking

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unique_id',
        'owner_id',
        'name',
        'species_id',
        'breed_id',
        'gender',
        'age_years',
        'age_months',
        'color',
        'weight',
        'distinguishing_marks',
        'microchip_number',
        'sterilization_status',
        'status',
        'created_via',
        'is_complete',
        'duplicate_of_uid',
        'is_duplicate',
    ];

    protected $casts = [
        'age_years' => 'integer',
        'age_months' => 'integer',
        'weight' => 'decimal:2',
        'is_complete' => 'boolean',
        'is_duplicate' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    // Duplicate relationship
    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'duplicate_of_uid', 'unique_id');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(Pet::class, 'duplicate_of_uid', 'unique_id');
    }

    // Helper methods
    public function getFormattedAgeAttribute(): string
    {
        if ($this->age_years && $this->age_months) {
            return $this->age_years . 'y ' . $this->age_months . 'm';
        } elseif ($this->age_years) {
            return $this->age_years . ' years';
        } elseif ($this->age_months) {
            return $this->age_months . ' months';
        }
        return 'Unknown';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? "Pet {$this->unique_id}";
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

    public function isDuplicate(): bool
    {
        return $this->is_duplicate === true;
    }

    public function hasDuplicates(): bool
    {
        return $this->duplicates()->count() > 0;
    }

    // Static methods for search
    public static function findByUid(string $uid): ?self
    {
        return self::where('unique_id', $uid)
                  ->with(['owner', 'species', 'breed'])
                  ->first();
    }

    public static function generateUniqueId(): string
    {
        $yearTwo = date('y');
        
        // Use transaction to ensure atomic UID generation
        return \DB::transaction(function () use ($yearTwo) {
            $counter = \DB::table('year_counters')
                ->where('year_two', $yearTwo)
                ->lockForUpdate()
                ->first();
                
            if (!$counter) {
                \DB::table('year_counters')->insert([
                    'year_two' => $yearTwo,
                    'last_seq' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $nextSeq = 1;
            } else {
                $nextSeq = $counter->last_seq + 1;
                \DB::table('year_counters')
                    ->where('year_two', $yearTwo)
                    ->update([
                        'last_seq' => $nextSeq,
                        'updated_at' => now(),
                    ]);
            }
            
            return $yearTwo . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        });
    }

    // Scope for incomplete profiles
    public function scopeIncomplete($query)
    {
        return $query->where('is_complete', false);
    }

    // Scope for active pets (not duplicates)
    public function scopeActive($query)
    {
        return $query->where('is_duplicate', false)->where('status', 'active');
    }
}
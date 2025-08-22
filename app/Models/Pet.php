<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

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
        'dob',
        'age_years',
        'age_months',
        'color',
        'weight',
        'microchip',
        'distinguishing_marks',
        'status',
        'is_sample_data',
        'metadata'
    ];

    protected $casts = [
        'dob' => 'date',
        'weight' => 'decimal:2',
        'is_sample_data' => 'boolean',
        'metadata' => 'json'
    ];

    /**
     * Get the pet's owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Get the pet's species
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * Get the pet's breed
     */
    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    /**
     * Get the pet's visits
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Get the pet's documents
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the pet's preventive plans
     */
    public function preventivePlans(): HasMany
    {
        return $this->hasMany(PreventivePlan::class);
    }

    /**
     * Get today's visit
     */
    public function todaysVisit()
    {
        return $this->hasOne(Visit::class)
            ->whereDate('visit_date', today())
            ->latest('visit_seq');
    }

    /**
     * Get latest visit
     */
    public function latestVisit()
    {
        return $this->hasOne(Visit::class)->latest('visit_date')->latest('visit_seq');
    }

    /**
     * Scope for provisional records
     */
    public function scopeProvisional($query)
    {
        return $query->where('status', 'provisional');
    }

    /**
     * Scope for active records
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Calculate age from DOB or age fields
     */
    public function getAgeAttribute()
    {
        if ($this->dob) {
            $years = $this->dob->diffInYears(now());
            $months = $this->dob->copy()->addYears($years)->diffInMonths(now());
            
            if ($years > 0) {
                return $years . ' year' . ($years > 1 ? 's' : '') . 
                       ($months > 0 ? ' ' . $months . ' month' . ($months > 1 ? 's' : '') : '');
            } else {
                return $months . ' month' . ($months > 1 ? 's' : '');
            }
        }
        
        if ($this->age_years || $this->age_months) {
            $parts = [];
            if ($this->age_years) {
                $parts[] = $this->age_years . ' year' . ($this->age_years > 1 ? 's' : '');
            }
            if ($this->age_months) {
                $parts[] = $this->age_months . ' month' . ($this->age_months > 1 ? 's' : '');
            }
            return implode(' ', $parts);
        }
        
        return 'Unknown';
    }

    /**
     * Get display name with species
     */
    public function getDisplayNameAttribute()
    {
        $parts = [$this->name];
        
        if ($this->species) {
            $parts[] = '(' . $this->species->common_name . ')';
        }
        
        return implode(' ', $parts);
    }

    /**
     * Get siblings (other pets of same owner)
     */
    public function getSiblingsAttribute()
    {
        return Pet::where('owner_id', $this->owner_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    /**
     * Check if pet information is complete
     */
    public function isComplete(): bool
    {
        return $this->status === 'active' 
            && !empty($this->name) 
            && $this->name !== 'Provisional Pet'
            && !empty($this->species_id)
            && !empty($this->gender);
    }

    /**
     * Create or get today's visit
     */
    public function ensureTodaysVisit($source = 'web')
    {
        $today = now()->toDateString();
        
        $visit = $this->visits()
            ->whereDate('visit_date', $today)
            ->first();
        
        if (!$visit) {
            $lastSeq = $this->visits()
                ->whereDate('visit_date', $today)
                ->max('visit_seq') ?? 0;
            
            $visit = $this->visits()->create([
                'uuid' => \Str::uuid(),
                'visit_date' => $today,
                'visit_seq' => $lastSeq + 1,
                'status' => 'open',
                'source' => $source
            ]);
        }
        
        return $visit;
    }
}
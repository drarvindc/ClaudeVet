<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Species extends Model
{
    protected $fillable = ['name', 'common_name'];

    public function breeds(): HasMany
    {
        return $this->hasMany(Breed::class);
    }

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }
}
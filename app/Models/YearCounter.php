<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YearCounter extends Model
{
    protected $fillable = ['year_two', 'last_seq'];
    
    public $timestamps = false;
    
    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
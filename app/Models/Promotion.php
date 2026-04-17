<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'discount_percentage',
        'start_date',
        'end_date',
        'applies_to_type',
        'scope_type',
        'target_ids',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'target_ids' => 'array',
    ];

    /**
     * Scope a query to only include currently active promotions.
     */
    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', today())
                     ->where('end_date', '>=', today());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Excursion extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'duree',
        'lieux_visites',
        'prix_par_personne',
        'nombre_personnes_min',
        'nombre_personnes_max',
        'actif',
        'image',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'prix_par_personne' => 'decimal:2',
    ];

    /**
     * Accessor pour le pourcentage de promotion actif
     */
    public function getActivePromotionPercentAttribute()
    {
        static $promotions = null;
        if ($promotions === null) {
            $promotions = \App\Models\Promotion::active()
                ->whereIn('applies_to_type', ['excursion', 'both'])
                ->get();
        }

        $highestDiscount = 0;
        foreach ($promotions as $promo) {
            if ($promo->scope_type === 'all') {
                if ($promo->discount_percentage > $highestDiscount) {
                    $highestDiscount = $promo->discount_percentage;
                }
            } elseif ($promo->scope_type === 'specific' && is_array($promo->target_ids)) {
                // target_ids could contain strings or integers, check loosely
                if (in_array($this->id, $promo->target_ids) || in_array((string)$this->id, $promo->target_ids, true)) {
                    if ($promo->discount_percentage > $highestDiscount) {
                        $highestDiscount = $promo->discount_percentage;
                    }
                }
            }
        }
        return $highestDiscount;
    }

    /**
     * Accessor pour le prix avec la promotion la plus forte appliquée
     */
    public function getPrixFinalAttribute()
    {
        $discount = $this->active_promotion_percent;
        if ($discount > 0) {
            return round((float)$this->prix_par_personne * (1 - ($discount / 100)), 2);
        }
        return (float)$this->prix_par_personne;
    }

    /**
     * Accessor pour l'URL de l'image
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            // Check if it's already a full URL
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                return $this->image;
            }
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image);
        }
        return null;
    }

    protected $appends = ['image_url', 'active_promotion_percent', 'prix_final'];
}

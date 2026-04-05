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

    protected $appends = ['image_url'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;

class Vehicule extends Model
{
    use HasFactory;

    /**
     * Les attributs qui peuvent être assignés massivement.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'marque',
        'modele',
        'immatriculation',
        'annee',
        'categorie',
        'transmission',
        'carburant',
        'statut',
        'prix_base',
        'description',
        'image',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'annee' => 'integer',
        'prix_base' => 'decimal:2',
    ];

    /**
     * Accessor pour l'URL de l'image
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return Storage::disk('public')->url($this->image);
        }
        return null;
    }

    protected $appends = ['image_url'];

    /**
     * Scope pour les véhicules disponibles
     */
    public function scopeDisponible($query)
    {
        return $query->where('statut', 'disponible');
    }

    /**
     * Scope pour les véhicules par catégorie
     */
    public function scopeParCategorie($query, $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    /**
     * Relation avec les documents du véhicule
     */
    public function documents()
    {
        return $this->hasMany(DocumentVehicule::class);
    }

    /**
     * Relation avec les maintenances du véhicule
     */
    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    /**
     * Relation avec les réservations du véhicule
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Surcharge dynamique du statut pour afficher "reservé" en temps réel (aujourd'hui)
     */
    public function getStatutAttribute($value)
    {
        if ($value === 'maintenance') {
            return $value;
        }

        $isRentedToday = $this->reservations()
            ->where('statut', 'confirme')
            ->whereDate('date_debut', '<=', now())
            ->whereDate('date_fin', '>=', now())
            ->exists();

        if ($isRentedToday) {
            return 'reservé';
        }

        return $value;
    }
}
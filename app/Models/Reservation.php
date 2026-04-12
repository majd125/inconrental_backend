<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'vehicule_id',
        'modele',
        'date_debut',
        'date_fin',
        'nb_participants',
        'lieu_depart',
        'lieu_arrivee',
        'montant_total',
        'statut',
        'option_chauffeur',
        'nb_sieges_bebe',
        'cancelled_by_id'
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'option_chauffeur' => 'boolean',
        'montant_total' => 'decimal:2',
    ];

    /**
     * Get the user who made the reservation.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Get the vehicle being reserved.
     */
    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id');
    }

    /**
     * Get the user who cancelled the reservation.
     */
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferReservation extends Model
{
    protected $fillable = [
        'utilisateur_id',
        'chauffeur_id',
        'lieu_depart',
        'lieu_destination',
        'date_heure_depart',
        'type_trajet',
        'duree_attente',
        'date_heure_retour',
        'nb_adultes',
        'nb_enfants',
        'nb_bebes',
        'montant_total',
        'statut',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function chauffeur()
    {
        return $this->belongsTo(User::class, 'chauffeur_id');
    }
}

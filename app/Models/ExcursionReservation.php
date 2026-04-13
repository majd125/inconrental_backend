<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExcursionReservation extends Model
{
    protected $fillable = [
        'utilisateur_id',
        'excursion_id',
        'date_reservation',
        'lieu_depart',
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

    public function excursion()
    {
        return $this->belongsTo(Excursion::class);
    }
}

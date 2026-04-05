<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVehicule extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicule_id',
        'type',
        'numero',
        'date_debut',
        'date_expiration',
        'organisme',
        'montant',
        'statut',
        'Remarques',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_expiration' => 'date',
        'montant' => 'decimal:2',
    ];

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }
}

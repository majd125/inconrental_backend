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
    ];

    protected $casts = [
        'actif' => 'boolean',
        'prix_par_personne' => 'decimal:2',
    ];
}

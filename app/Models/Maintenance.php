<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicule_id',
        'nom_maintenance',
        'date',
        'kilometrage',
        'description',
        'pieces_changees',
        'cout_piece',
        'cout_main_oeuvre',
        'cout_total',
        'garage',
        'prochaine_echeance_km',
        'prochaine_echeance_date',
        'statut',
        'remarques',
        'is_archived',
        'assigned_driver_id',
    ];

    protected $casts = [
        'date' => 'date',
        'prochaine_echeance_date' => 'date',
        'kilometrage' => 'integer',
        'prochaine_echeance_km' => 'integer',
        'cout_piece' => 'decimal:2',
        'cout_main_oeuvre' => 'decimal:2',
        'cout_total' => 'decimal:2',
        'is_archived' => 'boolean',
    ];

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }
}

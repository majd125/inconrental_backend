<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferReservation extends Model
{
    protected $fillable = [
        'user_id',
        'pickup_location',
        'destination',
        'pickup_datetime',
        'trip_type',
        'wait_duration',
        'return_datetime',
        'adults',
        'children',
        'babies',
        'quoted_price',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExcursionReservationController extends Controller
{
    public function index(Request $request)
    {
        $reservations = \App\Models\ExcursionReservation::with('excursion')
            ->where('utilisateur_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $reservations
        ]);
    }

    public function all()
    {
        $reservations = \App\Models\ExcursionReservation::with(['excursion', 'utilisateur'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $reservations
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'excursion_id' => 'required|exists:excursions,id',
            'date_reservation' => 'required|date|after_or_equal:today',
            'lieu_depart' => 'required|string',
            'nb_adultes' => 'required|integer|min:1',
            'nb_enfants' => 'integer|min:0',
            'nb_bebes' => 'integer|min:0',
        ]);

        $excursion = \App\Models\Excursion::findOrFail($validatedData['excursion_id']);

        // Check min/max people
        $total_people = $validatedData['nb_adultes'] + ($validatedData['nb_enfants'] ?? 0) + ($validatedData['nb_bebes'] ?? 0);
        if ($total_people < $excursion->nombre_personnes_min) {
            return response()->json(['message' => "Le nombre minimum de personnes pour cette excursion est {$excursion->nombre_personnes_min}."], 422);
        }
        if ($total_people > $excursion->nombre_personnes_max) {
            return response()->json(['message' => "Le nombre maximum de personnes pour cette excursion est {$excursion->nombre_personnes_max}."], 422);
        }

        // Calculate price (logic from frontend)
        $price_per_person = $excursion->prix_par_personne;
        $total_price = ($price_per_person * $validatedData['nb_adultes']) +
                       ($price_per_person * 0.8 * ($validatedData['nb_enfants'] ?? 0)) +
                       ($price_per_person * 0.5 * ($validatedData['nb_bebes'] ?? 0));

        $reservation = \App\Models\ExcursionReservation::create([
            'utilisateur_id' => $request->user()?->id,
            'excursion_id' => $validatedData['excursion_id'],
            'date_reservation' => $validatedData['date_reservation'],
            'lieu_depart' => $validatedData['lieu_depart'],
            'nb_adultes' => $validatedData['nb_adultes'],
            'nb_enfants' => $validatedData['nb_enfants'] ?? 0,
            'nb_bebes' => $validatedData['nb_bebes'] ?? 0,
            'montant_total' => $total_price,
            'statut' => 'en_attente',
        ]);

        return response()->json([
            'message' => 'Réservation effectuée avec succès',
            'data' => $reservation
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $validatedData = $request->validate([
            'statut' => 'required|in:en_attente,confirme,annule,termine'
        ]);

        $reservation = \App\Models\ExcursionReservation::findOrFail($id);
        
        // Basic security: if not admin, can only cancel their own
        if (!$request->user()->is_admin && $reservation->utilisateur_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservation->update(['statut' => $validatedData['statut']]);

        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'data' => $reservation
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransferReservation;
use Illuminate\Http\Request;

class TransferReservationController extends Controller
{
    public function index(Request $request)
    {
        $reservations = TransferReservation::where('utilisateur_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $reservations
        ]);
    }

    public function all()
    {
        $reservations = TransferReservation::with(['utilisateur', 'chauffeur'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $reservations
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'pickup' => 'required|string',
            'destination' => 'required|string',
            'datetime' => 'required|date',
            'tripType' => 'required|string',
            'waitDuration' => 'nullable|string',
            'returnDatetime' => 'nullable|string',
            'adults' => 'required|integer|min:1',
            'children' => 'integer|min:0',
            'babies' => 'integer|min:0',
        ]);

        $reservation = TransferReservation::create([
            'utilisateur_id' => $request->user()->id,
            'lieu_depart' => $validatedData['pickup'],
            'lieu_destination' => $validatedData['destination'],
            'date_heure_depart' => $validatedData['datetime'],
            'type_trajet' => $validatedData['tripType'],
            'duree_attente' => $validatedData['waitDuration'],
            'date_heure_retour' => !empty($validatedData['returnDatetime']) ? $validatedData['returnDatetime'] : null,
            'nb_adultes' => $validatedData['adults'],
            'nb_enfants' => $validatedData['children'] ?? 0,
            'nb_bebes' => $validatedData['babies'] ?? 0,
            'statut' => 'en_attente_prix',
        ]);

        return response()->json([
            'message' => 'Demande de devis envoyée avec succès',
            'data' => $reservation
        ], 201);
    }

    public function setPrice(Request $request, $id)
    {
        $validatedData = $request->validate([
            'quoted_price' => 'required|numeric|min:0'
        ]);

        $reservation = TransferReservation::findOrFail($id);
        
        $reservation->update([
            'montant_total' => $validatedData['quoted_price'],
            'statut' => 'en_attente_confirmation'
        ]);

        return response()->json([
            'message' => 'Prix envoyé au client',
            'data' => $reservation
        ]);
    }

    public function confirm(Request $request, $id)
    {
        $reservation = TransferReservation::findOrFail($id);
        
        if ($reservation->utilisateur_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservation->update(['statut' => 'confirme']);

        return response()->json([
            'message' => 'Réservation confirmée avec succès',
            'data' => $reservation
        ]);
    }

    public function getChauffeurs()
    {
        $chauffeurs = \App\Models\User::where('is_driver', true)->get();
        return response()->json([
            'data' => $chauffeurs
        ]);
    }

    public function assignChauffeur(Request $request, $id)
    {
        $validatedData = $request->validate([
            'chauffeur_id' => 'nullable|exists:users,id'
        ]);

        $reservation = TransferReservation::findOrFail($id);
        
        $reservation->update([
            'chauffeur_id' => $validatedData['chauffeur_id']
        ]);

        return response()->json([
            'message' => 'Chauffeur mis à jour avec succès',
            'data' => $reservation->load('chauffeur')
        ]);
    }

    public function chauffeurMissions(Request $request)
    {
        if (!$request->user()->is_driver) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $missions = TransferReservation::with('utilisateur')
            ->where('chauffeur_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $missions
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        // Accept both 'status' and 'statut' for compatibility
        $newStatus = $request->input('status') ?? $request->input('statut');

        if (!$newStatus) {
            return response()->json(['message' => 'Status field is required'], 422);
        }

        $reservation = TransferReservation::findOrFail($id);
        
        $user = $request->user();
        
        // Authorization: Admin, the Owner, or the assigned Chauffeur
        $isOwner = $reservation->utilisateur_id == $user->id;
        $isChauffeur = $reservation->chauffeur_id == $user->id;
        
        if (!$user->is_admin && !$isOwner && !$isChauffeur) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Specifically check if a chauffeur is trying to do something other than 'termine' (optional but good)
        if ($isChauffeur && !$user->is_admin && !in_array($newStatus, ['termine', 'confirme'])) {
             // Let them mark as done or keep as confirmed
        }

        $reservation->update(['statut' => $newStatus]);

        return response()->json([
            'message' => 'Statut mis à jour',
            'data' => $reservation
        ]);
    }
}

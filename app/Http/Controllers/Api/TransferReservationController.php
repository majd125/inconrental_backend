<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransferReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function getChauffeurs(Request $request)
    {
        $reservationId = $request->query('reservation_id');
        $chauffeurs = \App\Models\User::where('is_driver', true)->get();

        if ($reservationId) {
            $reservation = TransferReservation::findOrFail($reservationId);
            $startTime = $reservation->date_heure_depart;
            
            // Assume 2 hour window if no return time is set
            $endTime = $reservation->date_heure_retour ?: date('Y-m-d H:i:s', strtotime($startTime . ' + 2 hours'));

            // Find busy chauffeurs (already assigned to overlapping missions)
            $busyChauffeurIds = TransferReservation::where('id', '!=', $reservationId)
                ->whereNotNull('chauffeur_id')
                ->whereNotIn('statut', ['annule', 'en_attente_prix'])
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('date_heure_depart', '<', $endTime)
                      ->where(\DB::raw('COALESCE(date_heure_retour, DATE_ADD(date_heure_depart, INTERVAL 2 HOUR))'), '>', $startTime);
                })
                ->pluck('chauffeur_id')
                ->toArray();

            $chauffeurs->map(function($c) use ($busyChauffeurIds) {
                $c->is_busy = in_array($c->id, $busyChauffeurIds);
                return $c;
            });
        }

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
        
        if ($validatedData['chauffeur_id']) {
            $startTime = $reservation->date_heure_depart;
            $endTime = $reservation->date_heure_retour ?: date('Y-m-d H:i:s', strtotime($startTime . ' + 2 hours'));

            // Final check on availability
            $isBusy = TransferReservation::where('id', '!=', $id)
                ->where('chauffeur_id', $validatedData['chauffeur_id'])
                ->whereNotIn('statut', ['annule', 'en_attente_prix'])
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('date_heure_depart', '<', $endTime)
                      ->where(\DB::raw('COALESCE(date_heure_retour, DATE_ADD(date_heure_depart, INTERVAL 2 HOUR))'), '>', $startTime);
                })
                ->exists();

            if ($isBusy) {
                return response()->json([
                    'message' => 'Ce chauffeur a déjà une mission prévue à cette heure.'
                ], 422);
            }
        }

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

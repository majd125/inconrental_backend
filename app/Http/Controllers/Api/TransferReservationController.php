<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransferReservation;
use Illuminate\Http\Request;

class TransferReservationController extends Controller
{
    public function index(Request $request)
    {
        $reservations = TransferReservation::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $reservations
        ]);
    }

    public function all()
    {
        $reservations = TransferReservation::with('utilisateur')
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
            'returnDatetime' => 'nullable|string', // Changed to string temporarily to handle empty check
            'adults' => 'required|integer|min:1',
            'children' => 'integer|min:0',
            'babies' => 'integer|min:0',
        ]);

        $reservation = TransferReservation::create([
            'user_id' => $request->user()->id,
            'pickup_location' => $validatedData['pickup'],
            'destination' => $validatedData['destination'],
            'pickup_datetime' => $validatedData['datetime'],
            'trip_type' => $validatedData['tripType'],
            'wait_duration' => $validatedData['waitDuration'],
            'return_datetime' => !empty($validatedData['returnDatetime']) ? $validatedData['returnDatetime'] : null,
            'adults' => $validatedData['adults'],
            'children' => $validatedData['children'] ?? 0,
            'babies' => $validatedData['babies'] ?? 0,
            'status' => 'en_attente_prix',
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
            'quoted_price' => $validatedData['quoted_price'],
            'status' => 'en_attente_confirmation'
        ]);

        return response()->json([
            'message' => 'Prix envoyé au client',
            'data' => $reservation
        ]);
    }

    public function confirm(Request $request, $id)
    {
        $reservation = TransferReservation::findOrFail($id);
        
        if ($reservation->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservation->update(['status' => 'confirme']);

        return response()->json([
            'message' => 'Réservation confirmée avec succès',
            'data' => $reservation
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
        
        if (!$request->user()->is_admin && $reservation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservation->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Statut mis à jour',
            'data' => $reservation
        ]);
    }
}

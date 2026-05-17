<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Mail\ImplicitAccountCreated;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationConfirmed;
use Carbon\Carbon;

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
        $rules = [
            'excursion_id' => 'required|exists:excursions,id',
            'date_reservation' => 'required|date|after_or_equal:today',
            'lieu_depart' => 'required|string',
            'nb_adultes' => 'required|integer|min:1',
            'nb_enfants' => 'integer|min:0',
            'nb_bebes' => 'integer|min:0',
        ];

        $user = $request->user('sanctum');

        if (!$user) {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|email|max:255';
            $rules['telephone'] = 'required|string|max:20';
            $rules['cin'] = 'required|string|max:50';
        }

        $validatedData = $request->validate($rules);

        if (!$user) {
            // Check if user exists by email
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                // Create implicit account
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'telephone' => $request->telephone,
                    'cin' => $request->cin,
                    'password' => Hash::make($request->cin),
                    'is_admin' => false,
                    'is_staff' => false,
                    'is_driver' => false,
                ]);
                
                // Send email notification about implicit account creation
                try {
                    Mail::to($user->email)->send(new ImplicitAccountCreated($user, $request->cin));
                } catch (\Exception $e) {
                    \Log::error("Failed to send implicit account email: " . $e->getMessage());
                }
            }
        }

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
        $price_per_person = $excursion->prix_final;
        $total_price = ($price_per_person * $validatedData['nb_adultes']) +
                       ($price_per_person * 0.8 * ($validatedData['nb_enfants'] ?? 0)) +
                       ($price_per_person * 0.5 * ($validatedData['nb_bebes'] ?? 0));

        $reservation = \App\Models\ExcursionReservation::create([
            'utilisateur_id' => $user->id,
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

        $oldStatut = $reservation->statut;
        $reservation->update(['statut' => $validatedData['statut']]);

        // Send Email if Confirmed and previously not
        if ($validatedData['statut'] === 'confirme' && $oldStatut !== 'confirme') {
            $reservation->load(['excursion', 'utilisateur']);
            $user = $reservation->utilisateur;
            if ($user && $user->email) {
                $details = [
                    'Excursion' => $reservation->excursion ? $reservation->excursion->titre : 'Excursion',
                    'Lieu de départ' => $reservation->lieu_depart,
                    'Date' => Carbon::parse($reservation->date_reservation)->format('d/m/Y'),
                    'Participants' => ($reservation->nb_adultes + $reservation->nb_enfants + $reservation->nb_bebes) . ' personne(s)',
                    'Montant total' => round($reservation->montant_total) . ' TND',
                ];
                try {
                    Mail::to($user->email)->send(new ReservationConfirmed('Excursion', $details));
                } catch (\Exception $e) {
                    \Log::error("Failed to send email: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'data' => $reservation
        ]);
    }
}

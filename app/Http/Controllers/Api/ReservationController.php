<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * Display a listing of reservations for the authenticated user.
     */
    public function index(Request $request)
    {
        try {
            $reservations = Reservation::with('vehicule')
                ->where('utilisateur_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Inject placeholder vehicle for unassigned reservations
            $reservations->transform(function ($res) {
                if (!$res->vehicule) {
                    $placeholder = Vehicule::where('modele', $res->modele)->first();
                    if ($placeholder) {
                        $placeholder->immatriculation = 'Non assigné';
                        $res->setRelation('vehicule', $placeholder);
                    }
                }
                return $res;
            });

            return response()->json([
                'success' => true,
                'data' => $reservations
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des réservations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of ALL reservations (Admin only).
     */
    public function all(Request $request)
    {
        try {
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé. Réservé aux administrateurs.'
                ], 403);
            }

            $reservations = Reservation::with(['vehicule', 'user'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Inject placeholder vehicle for unassigned reservations
            $reservations->transform(function ($res) {
                if (!$res->vehicule) {
                    $placeholder = Vehicule::where('modele', $res->modele)->first();
                    if ($placeholder) {
                        $placeholder->immatriculation = 'Non assigné';
                        $res->setRelation('vehicule', $placeholder);
                    }
                }
                return $res;
            });

            return response()->json([
                'success' => true,
                'data' => $reservations
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de toutes les réservations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created reservation in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'vehicule_id' => 'required|exists:vehicules,id',
                'date_debut' => 'required|date|after_or_equal:today',
                'date_fin' => 'required|date|after:date_debut',
                'lieu_depart' => 'required|string|max:255',
                'lieu_arrivee' => 'nullable|string|max:255',
                'nb_participants' => 'integer|min:1',
                'option_chauffeur' => 'boolean',
                'nb_sieges_bebe' => 'integer|min:0'
            ]);

            $vehicule = Vehicule::findOrFail($validated['vehicule_id']);
            
            // Check availability for the requested model and dates
            $disponibles = Vehicule::where('modele', $vehicule->modele)
                ->where('statut', '!=', 'maintenance')
                ->get();
            $isAnyAvailable = false;

            foreach ($disponibles as $car) {
                $overlap = Reservation::where('vehicule_id', $car->id)
                    ->where('statut', 'confirme')
                    ->where(function ($query) use ($validated) {
                        $query->where('date_debut', '<', $validated['date_fin'])
                              ->where('date_fin', '>', $validated['date_debut']);
                    })->exists();
                
                if (!$overlap) {
                    $isAnyAvailable = true;
                    break;
                }
            }

            if (!$isAnyAvailable) {
                return response()->json([
                    'success' => false,
                    'message' => "Malheureusement, le modèle '{$vehicule->modele}' n'est pas disponible pour les dates choisies. Veuillez essayer d'autres dates, un autre modèle, ou utilisez le formulaire de recherche sur la page d'accueil pour voir les disponibilités réelles."
                ], 422);
            }

            // Calculate total price based on days
            $start = Carbon::parse($validated['date_debut']);
            $end = Carbon::parse($validated['date_fin']);
            $days = $start->diffInDays($end);
            if ($days == 0) $days = 1; // Minimum 1 day

            $montant_total = $days * $vehicule->prix_final;

            // Add baby seat cost ($10/day per seat)
            if ($request->has('nb_sieges_bebe') && $request->nb_sieges_bebe > 0) {
                $montant_total += ($days * 10 * $request->nb_sieges_bebe);
            }

            $reservation = Reservation::create([
                'utilisateur_id' => $request->user()->id,
                'vehicule_id' => null,
                'modele' => $vehicule->modele,
                'date_debut' => $validated['date_debut'],
                'date_fin' => $validated['date_fin'],
                'lieu_depart' => $validated['lieu_depart'],
                'lieu_arrivee' => $validated['lieu_arrivee'],
                'nb_participants' => $validated['nb_participants'] ?? 1,
                'option_chauffeur' => $validated['option_chauffeur'] ?? false,
                'nb_sieges_bebe' => $validated['nb_sieges_bebe'] ?? 0,
                'montant_total' => $montant_total,
                'statut' => 'en_attente'
            ]);

            $reservation->load('vehicule');
            if (!$reservation->vehicule) {
                $placeholder = Vehicule::where('modele', $reservation->modele)->first();
                if ($placeholder) {
                    $placeholder->immatriculation = 'Non assigné';
                    $reservation->setRelation('vehicule', $placeholder);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Réservation effectuée avec succès',
                'data' => $reservation
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la réservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update reservation status.
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'statut' => 'required|in:en_attente,confirme,annule,termine'
            ]);

            $reservation = Reservation::findOrFail($id);
            $user = $request->user();

            // Permission logic:
            if (!$user->is_admin) {
                // Non-admin can only update their own reservation
                if ($reservation->utilisateur_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accès refusé.'
                    ], 403);
                }
                
                // Non-admin can only request cancellation
                if ($request->statut !== 'annule') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Seule l\'annulation est autorisée.'
                    ], 403);
                }
            }

            // Cancellation specific logic
            if ($request->statut === 'annule') {
                // Check if the reservation has already started
                if (Carbon::parse($reservation->date_debut)->isPast()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible d\'annuler une réservation qui a déjà commencé ou qui est passée.'
                    ], 422);
                }
                
                // Track who cancelled it
                $reservation->cancelled_by_id = $user->id;
            }
            
            // Confirmation logic: check availability and assign a concrete vehicle
            if ($request->statut === 'confirme' && $reservation->statut !== 'confirme') {
                if (is_null($reservation->vehicule_id)) {
                    $disponibles = Vehicule::where('modele', $reservation->modele)
                        ->where('statut', '!=', 'maintenance')
                        ->get();
                    $assignedId = null;

                    foreach ($disponibles as $car) {
                        $overlap = Reservation::where('vehicule_id', $car->id)
                            ->where('statut', 'confirme')
                            ->where(function ($query) use ($reservation) {
                                $query->where('date_debut', '<', $reservation->date_fin)
                                      ->where('date_fin', '>', $reservation->date_debut);
                            })->exists();
                        
                        if (!$overlap) {
                            $assignedId = $car->id;
                            break;
                        }
                    }

                    if (!$assignedId) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Aucune voiture disponible pour ces dates.'
                        ], 422);
                    }

                    $reservation->vehicule_id = $assignedId;
                }
            }

            $reservation->statut = $request->statut;
            $reservation->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut de la réservation mis à jour avec succès',
                'data' => $reservation
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

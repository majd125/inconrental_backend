<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicule;
use Illuminate\Http\Request;

class VehiculeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Vehicule::query();
            
            // Filter by date if start and end are provided
            $start = $request->query('start');
            $end = $request->query('end');

            if ($start && $end) {
                $overlappingVehiculeIds = \App\Models\Reservation::whereNotNull('vehicule_id')
                    ->where('statut', 'confirme')
                    ->where(function ($q) use ($start, $end) {
                        $q->where('date_debut', '<', $end)
                          ->where('date_fin', '>', $start);
                    })
                    ->pluck('vehicule_id');
                    
                $query->whereNotIn('id', $overlappingVehiculeIds);
                
                // Exclude vehicles in maintenance/garage only when checking dates
                $query->where('statut', '!=', 'maintenance');
            }

            $vehicules = $query->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Liste des véhicules récupérée avec succès',
                'count' => $vehicules->count(),
                'data' => $vehicules
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des véhicules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé. Réservé aux administrateurs.'
                ], 403);
            }

            // Validation des données avec les règles ENUM
            $validated = $request->validate([
                'marque' => 'required|string|max:255',
                'modele' => 'required|string|max:255',
                'immatriculation' => 'required|string|unique:vehicules,immatriculation',
                'annee' => 'required|integer|min:1900|max:' . (date('Y') + 1),
                'categorie' => 'required|in:economique,compacte,berline,suv,luxe,sport',
                'transmission' => 'required|string|max:255',
                'carburant' => 'required|string|max:255',
                'statut' => 'required|in:disponible,reservé,maintenance',
                'prix_base' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'image' => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,gif,svg|max:2048' : 'nullable|string',
            ]);

            // Gestion de l'image
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('vehicules', 'public');
                $validated['image'] = $imagePath;
            }

            // Création du véhicule
            $vehicule = Vehicule::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Véhicule créé avec succès',
                'data' => $vehicule
            ], 201); // 201 = Created

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreur de validation
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422); // 422 = Unprocessable Entity

        } catch (\Exception $e) {
            // Autres erreurs
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du véhicule',
                'error' => $e->getMessage()
            ], 500); // 500 = Internal Server Error
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $vehicule = Vehicule::find($id);

            if (!$vehicule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Véhicule non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Véhicule récupéré avec succès',
                'data' => $vehicule
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du véhicule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $vehicule = Vehicule::find($id);

            if (!$vehicule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Véhicule non trouvé'
                ], 404);
            }

            // Validation des données
            $validated = $request->validate([
                'marque' => 'sometimes|string|max:255',
                'modele' => 'sometimes|string|max:255',
                'immatriculation' => 'sometimes|string|unique:vehicules,immatriculation,' . $id,
                'annee' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
                'categorie' => 'sometimes|in:economique,compacte,berline,suv,luxe,sport',
                'transmission' => 'sometimes|string|max:255',
                'carburant' => 'sometimes|string|max:255',
                'statut' => 'sometimes|in:disponible,reservé,maintenance',
                'prix_base' => 'sometimes|numeric|min:0',
                'description' => 'nullable|string',
            ]);

            // Mise à jour du véhicule
            $vehicule->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Véhicule mis à jour avec succès',
                'data' => $vehicule
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du véhicule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $vehicule = Vehicule::find($id);

            if (!$vehicule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Véhicule non trouvé'
                ], 404);
            }

            $vehicule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Véhicule supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du véhicule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available vehicles.
     */
    public function disponibles()
    {
        try {
            $vehicules = Vehicule::where('statut', 'disponible')->get();

            return response()->json([
                'success' => true,
                'message' => 'Véhicules disponibles récupérés avec succès',
                'count' => $vehicules->count(),
                'data' => $vehicules
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des véhicules disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vehicles by category.
     */
    public function parCategorie(string $categorie)
    {
        try {
            // Vérifier si la catégorie est valide
            $categoriesValides = ['economique', 'compacte', 'berline', 'suv', 'luxe', 'sport'];
            
            if (!in_array($categorie, $categoriesValides)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie invalide',
                    'valid_categories' => $categoriesValides
                ], 400);
            }

            $vehicules = Vehicule::where('categorie', $categorie)->get();

            return response()->json([
                'success' => true,
                'message' => 'Véhicules de la catégorie ' . $categorie . ' récupérés avec succès',
                'count' => $vehicules->count(),
                'data' => $vehicules
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des véhicules par catégorie',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
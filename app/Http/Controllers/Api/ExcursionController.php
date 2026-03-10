<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Excursion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExcursionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $excursions = Excursion::all();

            return response()->json([
                'success' => true,
                'message' => 'Liste des excursions récupérée avec succès',
                'count' => $excursions->count(),
                'data' => $excursions
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des excursions',
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
            $validated = $request->validate([
                'nom' => 'required|string|max:255',
                'description' => 'required|string',
                'duree' => 'required|string|max:255',
                'lieux_visites' => 'required|string',
                'prix_par_personne' => 'required|numeric|min:0',
                'nombre_personnes_min' => 'required|integer|min:1',
                'nombre_personnes_max' => 'required|integer|min:1|gte:nombre_personnes_min',
                'actif' => 'sometimes|boolean',
            ]);

            $excursion = Excursion::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Excursion créée avec succès',
                'data' => $excursion
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'excursion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $excursion = Excursion::find($id);

            if (!$excursion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Excursion non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Excursion récupérée avec succès',
                'data' => $excursion
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'excursion',
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
            $excursion = Excursion::find($id);

            if (!$excursion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Excursion non trouvée'
                ], 404);
            }

            $validated = $request->validate([
                'nom' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'duree' => 'sometimes|string|max:255',
                'lieux_visites' => 'sometimes|string',
                'prix_par_personne' => 'sometimes|numeric|min:0',
                'nombre_personnes_min' => 'sometimes|integer|min:1',
                'nombre_personnes_max' => 'sometimes|integer|min:1',
                'actif' => 'sometimes|boolean',
            ]);

            $excursion->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Excursion mise à jour avec succès',
                'data' => $excursion
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'excursion',
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
            $excursion = Excursion::find($id);

            if (!$excursion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Excursion non trouvée'
                ], 404);
            }

            $excursion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Excursion supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'excursion',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

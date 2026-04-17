<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    /**
     * Display a listing of all promotions.
     */
    public function index(Request $request)
    {
        try {
            // Admin only
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé. Réservé aux administrateurs.'
                ], 403);
            }

            $promotions = Promotion::orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $promotions
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des promotions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created promotion.
     */
    public function store(Request $request)
    {
        try {
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé. Réservé aux administrateurs.'
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'discount_percentage' => 'required|numeric|min:0|max:100',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'applies_to_type' => 'required|in:car,excursion,both',
                'scope_type' => 'required|in:all,specific',
                'target_ids' => 'nullable|array',
            ]);

            if ($validated['scope_type'] === 'specific' && empty($validated['target_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Si le scope est spécifique, target_ids est requis.'
                ], 422);
            }

            // Force empty target_ids to null if scope is all
            if ($validated['scope_type'] === 'all') {
                $validated['target_ids'] = null;
            }

            $promotion = Promotion::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Promotion créée avec succès',
                'data' => $promotion
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
                'message' => 'Erreur lors de la création de la promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified promotion in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé. Réservé aux administrateurs.'
                ], 403);
            }

            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion non trouvée'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'discount_percentage' => 'sometimes|numeric|min:0|max:100',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after_or_equal:start_date',
                'applies_to_type' => 'sometimes|in:car,excursion,both',
                'scope_type' => 'sometimes|in:all,specific',
                'target_ids' => 'nullable|array',
            ]);

            if (isset($validated['scope_type'])) {
                if ($validated['scope_type'] === 'specific' && empty($validated['target_ids']) && empty($promotion->target_ids)) {
                     // Note: Ideally we check the union of validated and existing
                     if (!isset($validated['target_ids']) || empty($validated['target_ids'])) {
                         return response()->json([
                             'success' => false,
                             'message' => 'Si le scope est spécifique, target_ids est requis.'
                         ], 422);
                     }
                }
                if ($validated['scope_type'] === 'all') {
                    $validated['target_ids'] = null;
                }
            }

            $promotion->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Promotion mise à jour avec succès',
                'data' => $promotion
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
                'message' => 'Erreur lors de la mise à jour de la promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified promotion from storage.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé. Réservé aux administrateurs.'
                ], 403);
            }

            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion non trouvée'
                ], 404);
            }

            $promotion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Promotion supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

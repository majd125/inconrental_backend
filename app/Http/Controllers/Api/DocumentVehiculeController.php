<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentVehicule;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentVehiculeController extends Controller
{
    /**
     * Display a listing of documents for a specific vehicle.
     */
    public function index(string $vehiculeId)
    {
        try {
            $vehicule = Vehicule::find($vehiculeId);
            if (!$vehicule) {
                return response()->json(['success' => false, 'message' => 'Véhicule non trouvé'], 404);
            }

            $documents = $vehicule->documents()->orderBy('date_expiration', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $documents
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la récupération des documents', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created document.
     */
    public function store(Request $request, string $vehiculeId)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            $vehicule = Vehicule::find($vehiculeId);
            if (!$vehicule) {
                return response()->json(['success' => false, 'message' => 'Véhicule non trouvé'], 404);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'nullable|string|in:carte_grise,assurance,vignette,visite_technique',
                'numero' => 'nullable|string',
                'date_debut' => 'nullable|date',
                'date_expiration' => 'nullable|date|after_or_equal:date_debut',
                'organisme' => 'nullable|string',
                'montant' => 'nullable|numeric',
                'statut' => 'nullable|in:validé,expiré',
                'Remarques' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $document = $vehicule->documents()->create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Document ajouté avec succès',
                'data' => $document
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de l\'ajout du document', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified document.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            $document = DocumentVehicule::find($id);
            if (!$document) {
                return response()->json(['success' => false, 'message' => 'Document non trouvé'], 404);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'nullable|string|in:carte_grise,assurance,vignette,visite_technique',
                'numero' => 'nullable|string',
                'date_debut' => 'nullable|date',
                'date_expiration' => 'nullable|date|after_or_equal:date_debut',
                'organisme' => 'nullable|string',
                'montant' => 'nullable|numeric',
                'statut' => 'nullable|in:validé,expiré',
                'Remarques' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $document->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Document mis à jour avec succès',
                'data' => $document
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la mise à jour du document', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            $document = DocumentVehicule::find($id);
            if (!$document) {
                return response()->json(['success' => false, 'message' => 'Document non trouvé'], 404);
            }

            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression du document', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of all documents across all vehicles sorted by expiration date.
     */
    public function allDocuments(Request $request)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            $documents = DocumentVehicule::with('vehicule')->orderBy('date_expiration', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $documents
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la récupération des documents globaux', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Renew an existing document.
     */
    public function renew(Request $request, string $id)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            $oldDoc = DocumentVehicule::find($id);
            if (!$oldDoc) {
                return response()->json(['success' => false, 'message' => 'Document non trouvé'], 404);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'nullable|string|in:carte_grise,assurance,vignette,visite_technique',
                'numero' => 'nullable|string',
                'date_debut' => 'nullable|date',
                'date_expiration' => 'nullable|date|after_or_equal:date_debut',
                'organisme' => 'nullable|string',
                'montant' => 'nullable|numeric',
                'statut' => 'nullable|in:validé,expiré',
                'Remarques' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            \Illuminate\Support\Facades\DB::beginTransaction();

            // Expire the old doc
            $oldDoc->update(['statut' => 'expiré']);

            // Determine data to create new doc
            $newData = $request->all();
            
            // To ensure consistency, force the vehicle ID and Type to match
            $newData['vehicule_id'] = $oldDoc->vehicule_id;
            // Fallback to old type if not provided
            $newData['type'] = $request->input('type', $oldDoc->type);
            
            $newDoc = DocumentVehicule::create($newData);

            \Illuminate\Support\Facades\DB::commit();

            // Return new doc along with its vehicle for the frontend
            $newDoc->load('vehicule');

            return response()->json([
                'success' => true,
                'message' => 'Document renouvelé avec succès',
                'data' => $newDoc
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur lors du renouvellement du document', 'error' => $e->getMessage()], 500);
        }
    }
}

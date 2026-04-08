<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of all maintenances across all vehicles.
     */
    public function allMaintenances(Request $request)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            $maintenances = Maintenance::with('vehicule')->orderBy('prochaine_echeance_date', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $maintenances
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la récupération des maintenances', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of maintenances for a specific vehicle.
     */
    public function index(string $vehiculeId)
    {
        try {
            $vehicule = Vehicule::find($vehiculeId);
            if (!$vehicule) {
                return response()->json(['success' => false, 'message' => 'Véhicule non trouvé'], 404);
            }

            // Triées par date décroissante (les plus récentes en premier)
            $maintenances = $vehicule->maintenances()->orderBy('date', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $maintenances
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la récupération des maintenances', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created maintenance.
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
                'nom_maintenance' => 'required|string',
                'date' => 'required|date',
                'kilometrage' => 'required|integer',
                'description' => 'nullable|string',
                'pieces_changees' => 'nullable|string',
                'cout_piece' => 'nullable|numeric',
                'cout_main_oeuvre' => 'nullable|numeric',
                'cout_total' => 'nullable|numeric',
                'garage' => 'nullable|string',
                'prochaine_echeance_km' => 'nullable|integer',
                'prochaine_echeance_date' => 'nullable|date',
                'statut' => 'required|in:en_cours,terminé',
                'remarques' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            // Ensure cout_total calculation falls back on input or summation if not strictly calculated on frontend
            $data = $request->all();
            if (!isset($data['cout_total'])) {
                $cp = $data['cout_piece'] ?? 0;
                $cmo = $data['cout_main_oeuvre'] ?? 0;
                $data['cout_total'] = $cp + $cmo;
            }

            $maintenance = $vehicule->maintenances()->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance ajoutée avec succès',
                'data' => $maintenance
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de l\'ajout de la maintenance', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified maintenance.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            $maintenance = Maintenance::find($id);
            if (!$maintenance) {
                return response()->json(['success' => false, 'message' => 'Maintenance non trouvée'], 404);
            }

            $validator = Validator::make($request->all(), [
                'nom_maintenance' => 'required|string',
                'date' => 'required|date',
                'kilometrage' => 'required|integer',
                'description' => 'nullable|string',
                'pieces_changees' => 'nullable|string',
                'cout_piece' => 'nullable|numeric',
                'cout_main_oeuvre' => 'nullable|numeric',
                'cout_total' => 'nullable|numeric',
                'garage' => 'nullable|string',
                'prochaine_echeance_km' => 'nullable|integer',
                'prochaine_echeance_date' => 'nullable|date',
                'statut' => 'required|in:en_cours,terminé',
                'remarques' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            if (isset($data['cout_piece']) || isset($data['cout_main_oeuvre'])) {
                if (!isset($data['cout_total'])) {
                    $cp = $data['cout_piece'] ?? $maintenance->cout_piece ?? 0;
                    $cmo = $data['cout_main_oeuvre'] ?? $maintenance->cout_main_oeuvre ?? 0;
                    $data['cout_total'] = $cp + $cmo;
                }
            }

            $maintenance->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mise à jour avec succès',
                'data' => $maintenance
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la mise à jour de la maintenance', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified maintenance.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            $maintenance = Maintenance::find($id);
            if (!$maintenance) {
                return response()->json(['success' => false, 'message' => 'Maintenance non trouvée'], 404);
            }

            $maintenance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance supprimée avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression de la maintenance', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Renew a scheduled maintenance.
     * Archives the old one and generates a new record.
     */
    public function renew(Request $request, string $id)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            $oldMain = Maintenance::find($id);
            if (!$oldMain) {
                return response()->json(['success' => false, 'message' => 'Maintenance originelle non trouvée'], 404);
            }

            $validator = Validator::make($request->all(), [
                'nom_maintenance' => 'required|string',
                'date' => 'required|date',
                'kilometrage' => 'required|integer',
                'description' => 'nullable|string',
                'pieces_changees' => 'nullable|string',
                'cout_piece' => 'nullable|numeric',
                'cout_main_oeuvre' => 'nullable|numeric',
                'cout_total' => 'nullable|numeric',
                'garage' => 'nullable|string',
                'prochaine_echeance_km' => 'nullable|integer',
                'prochaine_echeance_date' => 'nullable|date',
                'statut' => 'required|in:en_cours,terminé',
                'remarques' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();

            $oldMain->update(['is_archived' => true]);

            $newData = $request->all();
            if (!isset($newData['cout_total'])) {
                $cp = $newData['cout_piece'] ?? 0;
                $cmo = $newData['cout_main_oeuvre'] ?? 0;
                $newData['cout_total'] = $cp + $cmo;
            }
            
            // Link to same vehicle
            $newData['vehicule_id'] = $oldMain->vehicule_id;

            $newMain = Maintenance::create($newData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance renouvelée et archivée.',
                'data' => $newMain
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur lors du renouvellement', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark a maintenance as finished/received.
     */
    public function receive(Request $request, string $id)
    {
        try {
            // Admin Check
            if (!$request->user() || !$request->user()->is_admin) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            $maintenance = Maintenance::find($id);
            if (!$maintenance) {
                return response()->json(['success' => false, 'message' => 'Maintenance non trouvée'], 404);
            }

            $maintenance->statut = 'terminé';
            $maintenance->save();

            return response()->json([
                'success' => true, 
                'message' => 'Véhicule marqué comme reçu avec succès',
                'data' => $maintenance
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut', 'error' => $e->getMessage()], 500);
        }
    }
}

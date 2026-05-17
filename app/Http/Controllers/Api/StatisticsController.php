<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicule;
use App\Models\User;
use App\Models\Reservation;
use App\Models\ExcursionReservation;
use App\Models\TransferReservation;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        // 1. Vehicle Statistics
        $totalVehicles = Vehicule::count();
        $availableVehicles = Vehicule::where('statut', 'disponible')->count();
        $inMaintenanceVehicles = Vehicule::where('statut', 'maintenance')->count();
        
        // 2. User Statistics
        $totalUsers = User::count();
        $totalChauffeurs = User::where('is_driver', true)->count();
        
        // 3. Reservation Statistics
        $totalCarReservations = Reservation::count();
        $pendingCarReservations = Reservation::where('statut', 'en_attente')->count();
        
        $totalExcursionReservations = ExcursionReservation::count();
        $pendingExcursionReservations = ExcursionReservation::where('statut', 'en_attente')->count();
        
        $totalTransferReservations = TransferReservation::count();
        $pendingTransferReservations = TransferReservation::whereIn('statut', ['en_attente_prix', 'en_attente_confirmation'])->count();
        
        $totalReservations = $totalCarReservations + $totalExcursionReservations + $totalTransferReservations;
        
        // 4. Financial Statistics (Revenue)
        $carRevenue = Reservation::whereIn('statut', ['confirme', 'termine'])->sum('montant_total');
        $excursionRevenue = ExcursionReservation::whereIn('statut', ['confirme', 'termine'])->sum('montant_total');
        $transferRevenue = TransferReservation::whereIn('statut', ['confirme', 'termine'])->sum('montant_total');
        
        $totalRevenue = $carRevenue + $excursionRevenue + $transferRevenue;

        $monthlyCarRevenue = Reservation::whereIn('statut', ['confirme', 'termine'])
            ->where('created_at', '>=', $startOfMonth)
            ->sum('montant_total');
        $monthlyExcursionRevenue = ExcursionReservation::whereIn('statut', ['confirme', 'termine'])
            ->where('created_at', '>=', $startOfMonth)
            ->sum('montant_total');
        $monthlyTransferRevenue = TransferReservation::whereIn('statut', ['confirme', 'termine'])
            ->where('created_at', '>=', $startOfMonth)
            ->sum('montant_total');
            
        $monthlyRevenue = $monthlyCarRevenue + $monthlyExcursionRevenue + $monthlyTransferRevenue;

        // 5. Financial Statistics (Expenses)
        $totalExpenses = \App\Models\Maintenance::sum('cout_total');
        $monthlyExpenses = \App\Models\Maintenance::where('created_at', '>=', $startOfMonth)->sum('cout_total');

        // 6. Documents & Maintenances Statistics
        $totalDocuments = \App\Models\DocumentVehicule::count();
        $urgentDocuments = \App\Models\DocumentVehicule::where('statut', 'validé')
            ->whereNotNull('date_expiration')
            ->where('date_expiration', '<=', now()->addDays(30))
            ->count();
            
        $totalMaintenances = \App\Models\Maintenance::where(function($q) {
            $q->where('is_archived', false)->orWhereNull('is_archived');
        })->count();
        $urgentMaintenances = \App\Models\Maintenance::where(function($query) {
                $query->where('statut', 'en_cours')
                      ->orWhere(function($q) {
                          $q->whereNotNull('prochaine_echeance_date')
                            ->where('prochaine_echeance_date', '<=', now()->addDays(30));
                      });
            })
            ->where(function($q) {
                $q->where('is_archived', false)->orWhereNull('is_archived');
            })
            ->count();

        // 7. Recent Activity (Last 5 combined)
        $recentCar = Reservation::with('user', 'vehicule')->latest()->take(5)->get()->map(function($res) {
            return [
                'type' => 'car',
                'id' => $res->id,
                'user_name' => $res->user ? $res->user->name : 'Guest',
                'item_name' => $res->vehicule ? $res->vehicule->marque . ' ' . $res->vehicule->modele : 'Unknown',
                'status' => $res->statut,
                'date' => $res->created_at,
                'amount' => $res->montant_total
            ];
        });
        
        $recentExcursion = ExcursionReservation::with(['utilisateur', 'excursion'])->latest()->take(5)->get()->map(function($res) {
            return [
                'type' => 'excursion',
                'id' => $res->id,
                'user_name' => $res->utilisateur ? $res->utilisateur->name : 'Guest',
                'item_name' => $res->excursion ? $res->excursion->nom : 'Unknown',
                'status' => $res->statut,
                'date' => $res->created_at,
                'amount' => $res->montant_total
            ];
        });
        
        $recentTransfer = TransferReservation::with('utilisateur')->latest()->take(5)->get()->map(function($res) {
            return [
                'type' => 'transfer',
                'id' => $res->id,
                'user_name' => $res->utilisateur ? $res->utilisateur->name : 'Guest',
                'item_name' => $res->lieu_depart . ' -> ' . $res->lieu_destination,
                'status' => $res->statut,
                'date' => $res->created_at,
                'amount' => $res->montant_total ?? 0
            ];
        });
        
        // Merge and sort recent activity
        $recentActivity = collect($recentCar)
            ->merge($recentExcursion)
            ->merge($recentTransfer)
            ->sortByDesc('date')
            ->take(5)
            ->values();

        return response()->json([
            'vehicles' => [
                'total' => $totalVehicles,
                'available' => $availableVehicles,
                'in_maintenance' => $inMaintenanceVehicles,
            ],
            'users' => [
                'total' => $totalUsers,
                'chauffeurs' => $totalChauffeurs,
            ],
            'reservations' => [
                'total' => $totalReservations,
                'car_pending' => $pendingCarReservations,
                'excursion_pending' => $pendingExcursionReservations,
                'transfer_pending' => $pendingTransferReservations,
            ],
            'financials' => [
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'total_expenses' => $totalExpenses,
                'monthly_expenses' => $monthlyExpenses,
                'net_profit' => $totalRevenue - $totalExpenses,
                'monthly_net_profit' => $monthlyRevenue - $monthlyExpenses,
            ],
            'documents' => [
                'total' => $totalDocuments,
                'urgent' => $urgentDocuments,
            ],
            'maintenances' => [
                'total' => $totalMaintenances,
                'urgent' => $urgentMaintenances,
            ],
            'recent_activity' => $recentActivity
        ]);
    }
}

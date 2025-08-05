<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\FuelRecord;
use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard data
     */
    public function index(): JsonResponse
    {
        try {
            $currentMonth = now()->month;
            $currentYear = now()->year;
            
            // Gastos totales
            $totalExpenses = Expense::sum('amount');
            
            // Gastos del mes actual
            $monthlyExpenses = Expense::monthly($currentYear, $currentMonth)->sum('amount');
            
            // Gastos de combustible
            $fuelExpenses = Expense::byCategory('Combustible')->sum('amount');
            
            // Gastos de mantenimiento
            $maintenanceExpenses = Expense::byCategory('Mantenimiento')->sum('amount');
            
            // Recordatorios pendientes
            $pendingReminders = Reminder::pending()->count();
            
            // Recordatorios vencidos
            $overdueReminders = Reminder::overdue()->count();
            
            // Registros recientes de combustible
            $recentFuelRecords = FuelRecord::latest('date')->take(5)->get();
            
            // Gastos recientes
            $recentExpenses = Expense::latest('date')->take(5)->get();
            
            // Eficiencia de combustible
            $fuelEfficiency = FuelRecord::calculateFuelEfficiency();
            
            // Precio promedio por galón
            $averageCostPerGallon = FuelRecord::avg('price_per_gallon') ?? 0;
            
            // Gastos por categoría
            $expensesByCategory = Expense::selectRaw('category, SUM(amount) as total')
                ->groupBy('category')
                ->get()
                ->pluck('total', 'category');
            
            // Gastos de los últimos 6 meses
            $monthlyTrend = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthlyTrend[] = [
                    'month' => $date->format('M Y'),
                    'amount' => Expense::monthly($date->year, $date->month)->sum('amount')
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_expenses' => $totalExpenses,
                    'monthly_expenses' => $monthlyExpenses,
                    'fuel_expenses' => $fuelExpenses,
                    'maintenance_expenses' => $maintenanceExpenses,
                    'pending_reminders' => $pendingReminders,
                    'overdue_reminders' => $overdueReminders,
                    'recent_fuel_records' => $recentFuelRecords,
                    'recent_expenses' => $recentExpenses,
                    'fuel_efficiency' => round($fuelEfficiency, 2),
                    'average_cost_per_gallon' => round($averageCostPerGallon, 2),
                    'expenses_by_category' => $expensesByCategory,
                    'monthly_trend' => $monthlyTrend
                ],
                'message' => 'Datos del dashboard obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly report
     */
    public function monthlyReport(int $year, int $month): JsonResponse
    {
        try {
            // Gastos del mes
            $expenses = Expense::monthly($year, $month)->get();
            $totalExpenses = $expenses->sum('amount');
            
            // Registros de combustible del mes
            $fuelRecords = FuelRecord::monthly($year, $month)->get();
            $totalFuelCost = $fuelRecords->sum('cost');
            $totalGallons = $fuelRecords->sum('gallons');
            
            // Gastos por categoría
            $expensesByCategory = $expenses->groupBy('category')
                ->map(fn($categoryExpenses) => $categoryExpenses->sum('amount'));
            
            // Recordatorios del mes
            $monthStart = Carbon::create($year, $month, 1);
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            $reminders = Reminder::whereBetween('due_date', [$monthStart, $monthEnd])->get();
            $completedReminders = $reminders->where('is_completed', true)->count();
            $pendingReminders = $reminders->where('is_completed', false)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => "{$month}/{$year}",
                    'expenses' => [
                        'total' => $totalExpenses,
                        'count' => $expenses->count(),
                        'by_category' => $expensesByCategory,
                        'details' => $expenses
                    ],
                    'fuel' => [
                        'total_cost' => $totalFuelCost,
                        'total_gallons' => $totalGallons,
                        'average_price' => $totalGallons > 0 ? round($totalFuelCost / $totalGallons, 2) : 0,
                        'records_count' => $fuelRecords->count(),
                        'details' => $fuelRecords
                    ],
                    'reminders' => [
                        'total' => $reminders->count(),
                        'completed' => $completedReminders,
                        'pending' => $pendingReminders,
                        'details' => $reminders
                    ]
                ],
                'message' => "Reporte mensual de {$month}/{$year} generado exitosamente"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte mensual',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get yearly report
     */
    public function yearlyReport(int $year): JsonResponse
    {
        try {
            // Gastos del año
            $expenses = Expense::yearly($year)->get();
            $totalExpenses = $expenses->sum('amount');
            
            // Registros de combustible del año
            $fuelRecords = FuelRecord::yearly($year)->get();
            $totalFuelCost = $fuelRecords->sum('cost');
            $totalGallons = $fuelRecords->sum('gallons');
            
            // Gastos mensuales del año
            $monthlyExpenses = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthlyExpenses[] = [
                    'month' => $month,
                    'month_name' => Carbon::create($year, $month, 1)->format('M'),
                    'amount' => Expense::monthly($year, $month)->sum('amount')
                ];
            }
            
            // Gastos por categoría
            $expensesByCategory = $expenses->groupBy('category')
                ->map(fn($categoryExpenses) => $categoryExpenses->sum('amount'))
                ->sortDesc();
            
            // Recordatorios del año
            $yearStart = Carbon::create($year, 1, 1);
            $yearEnd = $yearStart->copy()->endOfYear();
            
            $reminders = Reminder::whereBetween('due_date', [$yearStart, $yearEnd])->get();
            $completedReminders = $reminders->where('is_completed', true)->count();
            $pendingReminders = $reminders->where('is_completed', false)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'summary' => [
                        'total_expenses' => $totalExpenses,
                        'total_fuel_cost' => $totalFuelCost,
                        'total_gallons' => $totalGallons,
                        'average_monthly_expense' => round($totalExpenses / 12, 2),
                        'fuel_efficiency' => FuelRecord::calculateFuelEfficiency()
                    ],
                    'monthly_expenses' => $monthlyExpenses,
                    'expenses_by_category' => $expensesByCategory,
                    'reminders' => [
                        'total' => $reminders->count(),
                        'completed' => $completedReminders,
                        'pending' => $pendingReminders
                    ]
                ],
                'message' => "Reporte anual de {$year} generado exitosamente"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte anual',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

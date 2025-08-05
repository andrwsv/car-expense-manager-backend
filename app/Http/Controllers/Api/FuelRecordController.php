<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelRecord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FuelRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $fuelRecords = FuelRecord::latest('date')->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $fuelRecords,
                'message' => 'Registros de combustible obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'date' => 'required|date',
                'gallons' => 'required|numeric|min:0',
                'cost' => 'required|numeric|min:0',
                'mileage' => 'required|integer|min:0',
                'gas_station' => 'nullable|string|max:255',
                'price_per_gallon' => 'required|numeric|min:0'
            ]);

            $fuelRecord = FuelRecord::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $fuelRecord,
                'message' => 'Registro de combustible creado exitosamente'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el registro de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $fuelRecord = FuelRecord::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $fuelRecord,
                'message' => 'Registro de combustible obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de combustible no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $fuelRecord = FuelRecord::findOrFail($id);
            
            $validatedData = $request->validate([
                'date' => 'sometimes|required|date',
                'gallons' => 'sometimes|required|numeric|min:0',
                'cost' => 'sometimes|required|numeric|min:0',
                'mileage' => 'sometimes|required|integer|min:0',
                'gas_station' => 'nullable|string|max:255',
                'price_per_gallon' => 'sometimes|required|numeric|min:0'
            ]);

            $fuelRecord->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => $fuelRecord->fresh(),
                'message' => 'Registro de combustible actualizado exitosamente'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $fuelRecord = FuelRecord::findOrFail($id);
            $fuelRecord->delete();

            return response()->json([
                'success' => true,
                'message' => 'Registro de combustible eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el registro de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fuel efficiency statistics
     */
    public function efficiency(): JsonResponse
    {
        try {
            $efficiency = FuelRecord::calculateFuelEfficiency();
            $records = FuelRecord::orderBy('date')->get();
            $averageCostPerGallon = $records->avg('price_per_gallon');
            $totalSpent = $records->sum('cost');
            $totalGallons = $records->sum('gallons');

            return response()->json([
                'success' => true,
                'data' => [
                    'efficiency' => round($efficiency, 2),
                    'records' => $records,
                    'average_cost_per_gallon' => round($averageCostPerGallon, 2),
                    'total_spent' => $totalSpent,
                    'total_gallons' => $totalGallons,
                    'records_count' => $records->count()
                ],
                'message' => 'Estadísticas de eficiencia obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de eficiencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly fuel records
     */
    public function monthly(int $year, int $month): JsonResponse
    {
        try {
            $fuelRecords = FuelRecord::monthly($year, $month)
                ->latest('date')
                ->get();

            $total = $fuelRecords->sum('cost');
            $totalGallons = $fuelRecords->sum('gallons');
            $averagePrice = $fuelRecords->avg('price_per_gallon');

            return response()->json([
                'success' => true,
                'data' => [
                    'records' => $fuelRecords,
                    'total' => $total,
                    'total_gallons' => $totalGallons,
                    'average_price' => round($averagePrice, 2),
                    'count' => $fuelRecords->count()
                ],
                'message' => "Registros de combustible de {$month}/{$year} obtenidos exitosamente"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener registros mensuales de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

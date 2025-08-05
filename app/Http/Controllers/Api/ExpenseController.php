<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $expenses = Expense::latest('date')->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $expenses,
                'message' => 'Gastos obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los gastos',
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
                'category' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string',
                'date' => 'required|date',
                'mileage' => 'nullable|integer|min:0'
            ]);

            $expense = Expense::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $expense,
                'message' => 'Gasto creado exitosamente'
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
                'message' => 'Error al crear el gasto',
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
            $expense = Expense::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $expense,
                'message' => 'Gasto obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gasto no encontrado',
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
            $expense = Expense::findOrFail($id);
            
            $validatedData = $request->validate([
                'category' => 'sometimes|required|string|max:255',
                'amount' => 'sometimes|required|numeric|min:0',
                'description' => 'sometimes|required|string',
                'date' => 'sometimes|required|date',
                'mileage' => 'nullable|integer|min:0'
            ]);

            $expense->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => $expense->fresh(),
                'message' => 'Gasto actualizado exitosamente'
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
                'message' => 'Error al actualizar el gasto',
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
            $expense = Expense::findOrFail($id);
            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Gasto eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el gasto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expenses by category
     */
    public function byCategory(string $category): JsonResponse
    {
        try {
            $expenses = Expense::byCategory($category)
                ->latest('date')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $expenses,
                'message' => "Gastos de la categoría {$category} obtenidos exitosamente"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener gastos por categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly expenses
     */
    public function monthly(int $year, int $month): JsonResponse
    {
        try {
            $expenses = Expense::monthly($year, $month)
                ->latest('date')
                ->get();

            $total = $expenses->sum('amount');
            $categoryTotals = $expenses->groupBy('category')
                ->map(fn($categoryExpenses) => $categoryExpenses->sum('amount'));

            return response()->json([
                'success' => true,
                'data' => [
                    'expenses' => $expenses,
                    'total' => $total,
                    'category_totals' => $categoryTotals,
                    'count' => $expenses->count()
                ],
                'message' => "Gastos de {$month}/{$year} obtenidos exitosamente"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener gastos mensuales',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

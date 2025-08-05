<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $reminders = Reminder::latest('due_date')->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $reminders,
                'message' => 'Recordatorios obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los recordatorios',
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
                'type' => 'required|string|max:255',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'due_date' => 'required|date|after:today',
                'mileage_interval' => 'nullable|integer|min:1',
                'current_mileage' => 'nullable|integer|min:0'
            ]);

            $reminder = Reminder::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $reminder,
                'message' => 'Recordatorio creado exitosamente'
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
                'message' => 'Error al crear el recordatorio',
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
            $reminder = Reminder::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $reminder,
                'message' => 'Recordatorio obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recordatorio no encontrado',
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
            $reminder = Reminder::findOrFail($id);
            
            $validatedData = $request->validate([
                'type' => 'sometimes|required|string|max:255',
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'due_date' => 'sometimes|required|date',
                'is_completed' => 'sometimes|boolean',
                'mileage_interval' => 'nullable|integer|min:1',
                'current_mileage' => 'nullable|integer|min:0'
            ]);

            $reminder->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => $reminder->fresh(),
                'message' => 'Recordatorio actualizado exitosamente'
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
                'message' => 'Error al actualizar el recordatorio',
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
            $reminder = Reminder::findOrFail($id);
            $reminder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Recordatorio eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el recordatorio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending reminders
     */
    public function pending(): JsonResponse
    {
        try {
            $pendingReminders = Reminder::pending()
                ->orderBy('due_date')
                ->get();

            $overdueCount = Reminder::overdue()->count();
            $upcomingCount = Reminder::upcoming(7)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'reminders' => $pendingReminders,
                    'overdue_count' => $overdueCount,
                    'upcoming_count' => $upcomingCount,
                    'total_pending' => $pendingReminders->count()
                ],
                'message' => 'Recordatorios pendientes obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recordatorios pendientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upcoming reminders
     */
    public function upcoming(int $days = 30): JsonResponse
    {
        try {
            $upcomingReminders = Reminder::upcoming($days)
                ->orderBy('due_date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $upcomingReminders,
                'message' => "Recordatorios próximos (próximos {$days} días) obtenidos exitosamente"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recordatorios próximos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark reminder as completed
     */
    public function markAsCompleted(string $id): JsonResponse
    {
        try {
            $reminder = Reminder::findOrFail($id);
            $reminder->markAsCompleted();

            return response()->json([
                'success' => true,
                'data' => $reminder->fresh(),
                'message' => 'Recordatorio marcado como completado'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar recordatorio como completado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

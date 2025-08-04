<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'description',
        'due_date',
        'is_completed',
        'email_sent',
        'mileage_interval',
        'current_mileage'
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_completed' => 'boolean',
        'email_sent' => 'boolean'
    ];

    // Scope para recordatorios pendientes
    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    // Scope para recordatorios vencidos
    public function scopeOverdue($query)
    {
        return $query->where('is_completed', false)
                    ->where('due_date', '<', now());
    }

    // Scope para recordatorios próximos
    public function scopeUpcoming($query, $days = 30)
    {
        return $query->where('is_completed', false)
                    ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    // Verificar si el recordatorio está vencido
    public function isOverdue()
    {
        return !$this->is_completed && $this->due_date->isPast();
    }

    // Marcar como completado
    public function markAsCompleted()
    {
        $this->update(['is_completed' => true]);
    }

    // Obtener días hasta el vencimiento
    public function getDaysUntilDue()
    {
        return now()->diffInDays($this->due_date, false);
    }
}

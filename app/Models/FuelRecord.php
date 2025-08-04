<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class FuelRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'gallons',
        'cost',
        'mileage',
        'gas_station',
        'price_per_gallon'
    ];

    protected $casts = [
        'date' => 'date',
        'gallons' => 'decimal:2',
        'cost' => 'decimal:2',
        'price_per_gallon' => 'decimal:2'
    ];

    // Scope para registros mensuales
    public function scopeMonthly($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    // Scope para registros anuales
    public function scopeYearly($query, $year)
    {
        return $query->whereYear('date', $year);
    }

    // Calcular eficiencia de combustible (km por galón)
    public static function calculateFuelEfficiency()
    {
        $records = self::orderBy('date')->get();
        $efficiencies = [];
        
        for ($i = 1; $i < $records->count(); $i++) {
            $current = $records[$i];
            $previous = $records[$i - 1];
            
            $distance = $current->mileage - $previous->mileage;
            if ($distance > 0 && $current->gallons > 0) {
                $efficiencies[] = $distance / $current->gallons;
            }
        }
        
        return count($efficiencies) > 0 ? array_sum($efficiencies) / count($efficiencies) : 0;
    }
}

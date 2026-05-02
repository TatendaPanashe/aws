<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class reports extends Model
{
    protected $table ='reports';
    use HasFactory;

    protected $fillable = [
        'date',
        'transaction_type',
        'amount',
        'deposits',
        'user_id',
    ];

    public function scopeFilterByDate($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate) {
            return $query->whereDate('date', '>=', $startDate);
        } elseif ($endDate) {
            return $query->whereDate('date', '<=', $endDate);
        }

        return $query;
    }

    public function scopeFilterByType($query, $transactionType)
    {
        if ($transactionType) {
            return $query->where('transaction_type', $transactionType);
        }

        return $query;
    }
}

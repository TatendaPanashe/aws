<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class icecash extends Model
{
    /** @use HasFactory<\Database\Factories\IcecashFactory> */
    use HasFactory;
    protected $table ='icecash';
    protected $fillable = [
        'amount',
        'transactions',
        'currency',
        'deposits', 
        'date',
        'transaction_type',
        'user_id',
    ];

    public function scopeFilterByDate($query, $startDate, $endDate,$request) 
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
            return $query->where('currency', $transactionType);
        }

        return $query;
    }
}

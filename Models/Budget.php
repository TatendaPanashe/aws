<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $table = 'budgets';

    protected $fillable = [
        'year',
        'month',
        'site_id',
        'network_id',
        'budgeted_amount_usd',
        'budgeted_amount_zwg',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'budgeted_amount_usd' => 'decimal:2',
        'budgeted_amount_zwg' => 'decimal:2',
    ];

    public function site()
    {
        return $this->belongsTo(site::class, 'site_id');
    }

    public function network()
    {
        return $this->belongsTo(Network::class, 'network_id');
    }
}

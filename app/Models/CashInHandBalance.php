<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashInHandBalance extends Model
{
    //

    use HasFactory;
      protected $table = 'cash_in_hand_balances';

    protected $fillable = [
        'clerk_id',
        'balance_zwg',
        'balance_usd',
    ];

    // Optional relation to User (if clerks are users)
    public function clerk()
    {
        return $this->belongsTo(\App\Models\User::class, 'clerk_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class third extends Model
{
    /** @use HasFactory<\Database\Factories\ThirdFactory> */
    use HasFactory;
    protected $table ='supervisor_facevalue';
    protected $fillable = [
        
        'total_transactions',
        'currency',
        'date',
        'transaction_type',
        'user_id',
    ];
}

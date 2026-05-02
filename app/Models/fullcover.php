<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class fullcover extends Model
{
    /** @use HasFactory<\Database\Factories\FullcoverFactory> */
    use HasFactory;
    protected $table ='fullcover';
    protected $fillable = [
        
        'deposits',
        'currency',
        'number_of_policies', 
        'date',
        'transaction_type',
        'user_id',
    ];
}

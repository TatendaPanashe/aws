<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CsvData extends Model
{
    /** @use HasFactory<\Database\Factories\CsvDataFactory> */
    use HasFactory;
    protected $fillable = [
        'id_number',
        'agent',
        'approved',
        'classification',
        'main_agent',
        'issue_date',
        'status',
        'vehicle_reg_no.',
        'start_date',
        'end_date',
        'policy_no',
        'location',
        'broker_name',
        'payment_method',
        'rta_amount',
        'insurance_type',
        'customer_name',
        'amount',
    ];

}

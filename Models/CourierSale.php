<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'face_value_id',
        'batch_id',
        'clerk_id',
        'supervisor_id',
        'network_id',
        'site_id',
        'insurance_provider',
        'currency',
        'sales_amount',
        'sale_date',
        'comments',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'sales_amount' => 'decimal:2',
    ];

    public function clerk()
    {
        return $this->belongsTo(User::class, 'clerk_id');
    }

    public function faceValue()
    {
        return $this->belongsTo(FaceValue::class, 'face_value_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}

<?php
// app/Models/FaceValueUsage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceValueUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_collection_id',
        'batch_id',
        'clerk_id',
        'network_id',
        'site_id',
        'platform_name',
        'used',
        'spoiled',
        'remaining',
        'usage_date',
        'comments',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'used' => 'integer',
        'spoiled' => 'integer',
        'remaining' => 'integer'
    ];

    public function dailyCollection()
    {
        return $this->belongsTo(DailyCollection::class);
    }

    public function batch()
    {
        return $this->belongsTo(FaceValue::class, 'batch_id');
    }

    public function clerk()
    {
        return $this->belongsTo(User::class);
    }

    public function network()
    {
        return $this->belongsTo(Network::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class, 'platform_name', 'platform_name');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'starting',
        'ending',
        'received',
        'used',
        'closing_balance',
        'opening_balance',
        'clerk_id',
        'supervisor_id',
        'batch_id',
        'is_parent',
        'parent_id',
        'spoiled',
        'comments',
        'insurance_provider',
        'document_channel',
        'batch_balance',
        'siteid',
        'networkid',
        'daily_collection_id',
    ];


    public function clerk()
    {
        return $this->belongsTo('App\Models\User', 'clerk_id');
    }
    public function supervisor()
    {
        return $this->belongsTo('App\Models\User', 'supervisor_id');
    }
    public function site()
    {
        return $this->belongsTo(Site::class, 'siteid');
    }
    public function assignedClerk()
{
    return $this->belongsTo(FaceValue::class);
}
public function allocations()
{
    return $this->hasMany(FaceValue::class, 'batch_id');
}
}

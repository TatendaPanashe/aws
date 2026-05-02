<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supervisorfacevalues extends Model
{
    //
    public $timestamps = true;
    use HasFactory;
    protected $table ='supervisor_facevalue';
    protected $fillable = [
        
    'starting',
    'ending',
    'received',
    'allocated', 
    'balance', 
    'user_id',
    'assigned_to',
    'batch_id',
    'new_starting',

];

protected $casts = [
    'created_at' => 'datetime',
];

public function user()
{
    return $this->belongsTo(User::class);
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
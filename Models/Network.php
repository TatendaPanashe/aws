<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Ensure this line is present
use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    //


    use HasFactory;
    protected $table ='network';
    protected $fillable = [
        
        'name',
        'city',
        //'province',
        'description',
        'user_id',
        
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function offices()
    {
        return $this->hasMany(site::class, 'network_id');
    }

    public function sites()
    {
        return $this->offices();
    }
}

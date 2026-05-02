<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class site extends Model
{
    /** @use HasFactory<\Database\Factories\SiteFactory> */
    use HasFactory;
    protected $table ='site';
    protected $fillable = [
        'site_name',
        'network_id',
        'site_description',
        'user_id',
        'code_name',
        'code',
        'sbu_id',
        'POS',
        'bank',
        'sbu',
      
        
    ];



    // Relationship to SBU
    public function sbu()
    {
        return $this->belongsTo(SBU::class, 'SBUId');
    }

    // If you want to get all users through SBU
    public function usersThroughSBU()
    {
        return $this->hasManyThrough(
            User::class,
            SBU::class,
            'id', // Foreign key on SBU table
            'SBUId', // Foreign key on User table
            'SBUId', // Local key on Site table
            'id' // Local key on SBU table
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function network()
    {
        return $this->belongsTo(Network::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'site_id');
    }
}

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
        'POS',
        'bank',
        'platform_name',
      
        
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function network()
    {
        return $this->belongsTo(Network::class);
    }

    public function region()
    {
        return $this->network();
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'site_id');
    }
    public function platform()
    {
        return $this->belongsTo(Platform::class, 'platform_name', 'platform_name');
    }
}

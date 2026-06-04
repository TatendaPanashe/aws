<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = ['platform_name', 'platform_description'];


    public function site()
    {
        return $this->belongsTo(Site::class,'site_id');
    }
}

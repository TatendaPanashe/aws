<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SBU extends Model
{
    //
     use HasFactory;

    protected $table = 'daily_collection_sbu_site';

    protected $fillable = [
        'name',
        'sbu_description',
        'superviser_id',
    ];
    public function site()
    {
        return $this->belongsTo(Site::class, 'SiteId');
    }
}

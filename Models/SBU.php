<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SBU extends Model
{
    //
     use HasFactory;

    protected $table = 'sbus';

    protected $fillable = [
        'SBUName',
        'SiteId',
        'SuperviserId',
    ];
    public function site()
    {
        return $this->belongsTo(Site::class, 'SiteId');
    }
}

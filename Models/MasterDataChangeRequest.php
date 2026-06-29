<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDataChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_type',
        'target_id',
        'action',
        'original_data',
        'requested_data',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
    ];

    protected $casts = [
        'original_data' => 'array',
        'requested_data' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function targetLabel(): string
    {
        $data = $this->requested_data ?: $this->original_data ?: [];

        if ($this->target_type === 'site') {
            return $data['site_name'] ?? 'Site #' . $this->target_id;
        }

        if ($this->target_type === 'network') {
            return $data['name'] ?? 'Network #' . $this->target_id;
        }

        return ucfirst($this->target_type) . ' #' . $this->target_id;
    }
}

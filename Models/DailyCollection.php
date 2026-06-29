<?php
// app/Models/DailyCollection.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DailyCollection extends Model
{
    use HasFactory;
    
    protected $table = 'daily_collections';
    
    protected $fillable = [
        'currency',
        'third_party_premiums',
        'full_cover_premiums',
        'zinara_fees',
        'usd_mpos',
        'zwg_mpos',
        'zwg_third_party_premiums',
        'zwg_full_cover_premiums',
        'zwg_zinara_fees',
        'usd_total_deposited',
        'zwg_total_deposited',
        'usd_cash',
        'usd_swipe',
        'usd_transfers',
        'zwg_cash',
        'zwg_swipe',
        'zwg_transfers',
        'bank',
        'cash_deposited',
        'zwg_cash_deposited',
        'comments',
        'user_id',
        'insurance_transactions',
        'zwg_insurance_transactions',
        'zinara_transactions',
        'username',
        'networkid',
        'siteid',
        'balance',
        'POS',
        'code',
        'site_name',
        'transaction_date',
        'zwg_debit_sales',
        'usd_debit_sales',
        'zwg_credit_sales',
        'usd_credit_sales',
        'usd_cash_in_hand',
        'usd_cash_in_hand_balance',
        'zwg_cash_in_hand',
        'zwg_cash_in_hand_balance',
        'other_insurances_zwg',
        'other_insurances_usd',
        'platform_name',
        'zinara_credential',
        'icecash_credential',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'transaction_date' => 'date',
        'insurance_transactions' => 'decimal:2',
        'zwg_insurance_transactions' => 'decimal:2',
        'usd_cash' => 'decimal:2',
        'zwg_cash' => 'decimal:2',
        'usd_total_deposited' => 'decimal:2',
        'zwg_total_deposited' => 'decimal:2',
    ];

    public function site()
    {
        return $this->belongsTo(site::class, 'siteid');
    }
    public function platform()
    {
        return $this->belongsTo(Platform::class, 'platform_name', 'platform_name');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function network()
    {
        return $this->belongsTo(Network::class, 'networkid');
    }

    public function faceValueUsages()
    {
        return $this->hasMany(FaceValueUsage::class);
    }

    // ✅ NEW: Scope for date range
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // ✅ NEW: Scope for specific date
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }

    // ✅ NEW: Scope for specific site
    public function scopeForSite($query, $siteId)
    {
        return $query->where('siteid', $siteId);
    }

    // ✅ NEW: Get missing dates for a site
    public static function getMissingDatesForSite($siteId, $startDate, $endDate, $userId = null)
    {
        $query = static::where('siteid', $siteId)
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $existingDates = $query->selectRaw('DATE(created_at) as date')
            ->distinct()
            ->pluck('date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();
        
        $allDates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        while ($current <= $end) {
            $allDates[] = $current->format('Y-m-d');
            $current->addDay();
        }
        
        return array_diff($allDates, $existingDates);
    }

    // ✅ NEW: Get submission status for calendar
    public static function getCalendarData($userId, $year, $month)
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        $user = User::find($userId);
        
        // Get sites accessible to this user
        if ($user->role->id == 3) { // Admin role
            $sites = Site::all();
        } else {
            $sites = Site::where('id', $user->siteid)->get();
        }
        
        // Get all submissions for the month
        $submissions = static::whereBetween('created_at', [$startDate, $endDate])
            ->when($user->role->id != 3, function($query) use ($userId) {
                return $query->where('user_id', $userId);
            })
            ->get()
            ->groupBy(function($submission) {
                return $submission->created_at->format('Y-m-d');
            });
        
        // Build calendar data
        $calendar = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $daySubmissions = $submissions->get($dateKey, collect());
            
            $submittedSiteIds = $daySubmissions->pluck('siteid')->toArray();
            $allSiteIds = $sites->pluck('id')->toArray();
            $missingSiteIds = array_diff($allSiteIds, $submittedSiteIds);
            
            $calendar[$dateKey] = [
                'date' => $currentDate->copy(),
                'date_formatted' => $currentDate->format('F j, Y'),
                'day_of_week' => $currentDate->format('l'),
                'is_today' => $currentDate->isToday(),
                'is_past' => $currentDate->isPast(),
                'is_future' => $currentDate->isFuture(),
                'has_submissions' => $daySubmissions->isNotEmpty(),
                'submissions_count' => $daySubmissions->count(),
                'total_sites' => $sites->count(),
                'submitted_sites_count' => $daySubmissions->count(),
                'missing_sites_count' => count($missingSiteIds),
                'is_complete' => $daySubmissions->count() === $sites->count() && $sites->count() > 0,
                'is_partial' => $daySubmissions->count() > 0 && $daySubmissions->count() < $sites->count(),
                'submissions' => $daySubmissions,
                'missing_sites' => Site::whereIn('id', $missingSiteIds)->get(),
                'total_usd' => $daySubmissions->sum('insurance_transactions'),
                'total_zwg' => $daySubmissions->sum('zwg_insurance_transactions'),
            ];
            
            $currentDate->addDay();
        }
        
        return [
            'calendar' => $calendar,
            'sites' => $sites,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_submissions' => $submissions->count(),
            'total_usd_collected' => $submissions->sum('insurance_transactions'),
            'total_zwg_collected' => $submissions->sum('zwg_insurance_transactions'),
        ];
    }
}

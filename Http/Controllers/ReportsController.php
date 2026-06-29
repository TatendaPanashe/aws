<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorereportsRequest;
use App\Http\Requests\UpdatereportsRequest;
use App\Models\Budget;
use App\Models\CollectionAmmendments;
use App\Models\CsvData;
use App\Models\DailyCollection;
use App\Models\FaceValue;
use App\Models\MasterDataChangeRequest;
use App\Models\Network;
use App\Models\reports;
use App\Models\reports as Report;
use App\Models\Supervisorfacevalues;
use App\Models\User;
use App\Models\site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportsController extends Controller
{
    private const COURIER_SUPERVISOR_ROLE_ID = 6;
    private const COURIER_PLATFORM = 'Courier Connect';

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function normalizeRegionName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim($value)));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Get the user's assigned region based on their network
     */
    private function getUserRegion()
    {
        $user = Auth::user();

        if ((int) $user->role_id === self::COURIER_SUPERVISOR_ROLE_ID) {
            return self::COURIER_PLATFORM;
        }
        
        if($user->network && $user->network->name) {
            return $this->normalizeRegionName($user->network->name);
        }
        
        return null;
    }

    private function getUserSBU()
    {
        return $this->getUserRegion();
    }

    private function getVisibleRegionIdsForReports(): array
    {
        $user = Auth::user();

        return $user?->networkid ? [(int) $user->networkid] : [];
    }

    private function getVisibleSbusForReports(): array
    {
        return Network::whereIn('id', $this->getVisibleRegionIdsForReports())
            ->pluck('name')
            ->toArray();
    }

    private function normalizeSbu(?string $value): ?string
    {
        return $this->normalizeRegionName($value);
    }

    private function getReportScopeLabel(): ?string
    {
        return $this->getUserRegion();
    }

    private function isCourierSupervisorRole(int $roleId): bool
    {
        return $roleId === self::COURIER_SUPERVISOR_ROLE_ID;
    }

    private function getCourierSupervisorSiteIds()
    {
        return User::whereIn('role_id', [2, 7])
            ->whereHas('site', function($q) {
                $q->whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)]);
            })
            ->pluck('siteid')
            ->filter()
            ->unique()
            ->values();
    }

    private function getCourierSupervisorAuthorizedClerkIds(): array
    {
        return User::whereIn('role_id', [2, 7])
            ->whereHas('site', function($q) {
                $q->whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)]);
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get authorized clerk IDs based on user's assigned region
     */
    private function getAuthorizedClerkIds()
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $visibleRegionIds = $this->getVisibleRegionIdsForReports();
        
        // Regional managers and supervisors see clerks in their assigned region.
        if (in_array($roleId, [3, 4], true) && !empty($visibleRegionIds)) {
            $clerkIds = User::whereIn('role_id', [2, 7])
                ->whereIn('networkid', $visibleRegionIds)
                ->pluck('id')
                ->toArray();
            
            return $clerkIds;
        }
        
        // For super admin or users without region, return empty (means no filter)
        return [];
    }

    /**
     * Display the reporting hub.
     */
    public function index()
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;

        if ($this->isCourierSupervisorRole($roleId)) {
            return $this->courierSupervisorIndex();
        }

        $userRegion = $this->getReportScopeLabel();
        $isRegionalManager = in_array($roleId, [3, 4], true);
        $authorizedClerkIds = $this->getAuthorizedClerkIds();
        
        $reportWindowStart = Carbon::now()->subDays(30);
        
        $collectionQuery = DailyCollection::query();
        if ($isRegionalManager && $userRegion && !empty($authorizedClerkIds)) {
            $collectionQuery->whereIn('user_id', $authorizedClerkIds);
        }
        
        $collectionWindow = Schema::hasTable('daily_collections')
            ? $collectionQuery->where('created_at', '>=', $reportWindowStart)->get()
            : collect();

        // Filter collection trend based on user's region
        $collectionTrendQuery = DB::table('daily_collections')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(insurance_transactions) as total_usd'),
                DB::raw('SUM(zwg_insurance_transactions) as total_zwg')
            )
            ->where('created_at', '>=', $reportWindowStart);

        if ($isRegionalManager && $userRegion && !empty($authorizedClerkIds)) {
            $collectionTrendQuery->whereIn('user_id', $authorizedClerkIds);
        }
        
        $collectionTrendRows = Schema::hasTable('daily_collections')
            ? $collectionTrendQuery->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy(DB::raw('DATE(created_at)'))
                ->get()
            : collect();

        $collectionTrendLabels = $collectionTrendRows->pluck('date')->toArray();
        $collectionTrendUsd = $collectionTrendRows->pluck('total_usd')->map(fn ($value) => (float) $value)->toArray();
        $collectionTrendZwg = $collectionTrendRows->pluck('total_zwg')->map(fn ($value) => (float) $value)->toArray();

        // CSV import data remains global unless a report-specific region filter is added.
        $csvRows = Schema::hasTable('csv_data') ? CsvData::all() : collect();
        $applicationCount = $csvRows->count();
        $applicationAmount = $csvRows->sum(fn ($row) => $this->parseCsvAmount($row->amount));

        // Filter face value stock based on user's region
        $faceValueQuery = Supervisorfacevalues::query();
        if ($isRegionalManager && $userRegion && !empty($authorizedClerkIds)) {
            $faceValueQuery->whereIn('assigned_to', $authorizedClerkIds)
                ->orWhere('user_id', $user->id);
        }
        
        $faceValueBalance = Schema::hasTable('supervisor_facevalue')
            ? $faceValueQuery->sum('balance')
            : 0;

        $statusBreakdown = $csvRows
            ->groupBy(fn ($row) => $row->status ?: 'Unknown')
            ->map(fn ($rows, $status) => [
                'label' => $status,
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        $locationBreakdown = $csvRows
            ->groupBy(fn ($row) => $row->location ?: 'Unknown')
            ->map(fn ($rows, $location) => [
                'label' => $location,
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        // Filter pending amendments based on user's region
        $amendmentQuery = CollectionAmmendments::query();
        if ($isRegionalManager && $userRegion && !empty($authorizedClerkIds)) {
            $amendmentQuery->whereIn('userid', $authorizedClerkIds);
        }
        
        $pendingAmendments = Schema::hasTable('collection_ammendments')
            ? $amendmentQuery->where('status', 'requested')->count()
            : 0;

        $masterDataChangeQuery = MasterDataChangeRequest::query()->where('status', 'pending');
        if ($roleId !== 5) {
            $masterDataChangeQuery->where('requested_by', $user->id);
        }

        $pendingMasterDataChanges = Schema::hasTable('master_data_change_requests')
            ? $masterDataChangeQuery->count()
            : 0;

        $summaryCards = [
            [
                'label' => 'USD Collections (30 Days)',
                'value' => '$' . number_format($collectionWindow->sum('insurance_transactions'), 2),
                'note' => 'Combined submitted USD activity in the last 30 days.' . ($userRegion ? " (Region: $userRegion)" : ''),
                'icon' => 'bi bi-cash-coin',
            ],
            [
                'label' => 'ZWG Collections (30 Days)',
                'value' => 'ZWG ' . number_format($collectionWindow->sum('zwg_insurance_transactions'), 2),
                'note' => 'Local currency collection movement in the last 30 days.' . ($userRegion ? " (Region: $userRegion)" : ''),
                'icon' => 'bi bi-wallet2',
            ],
            [
                'label' => 'Pending Amendments',
                'value' => number_format($pendingAmendments),
                'note' => 'Collection changes waiting review.',
                'icon' => 'bi bi-pencil-square',
            ],
            [
                'label' => 'Pending Site/Network Changes',
                'value' => number_format($pendingMasterDataChanges),
                'note' => $roleId === 5 ? 'Master data changes waiting super user approval.' : 'Your submitted site/network changes waiting approval.',
                'icon' => 'bi bi-shield-check',
            ],
            [
                'label' => 'Imported Applications',
                'value' => number_format($applicationCount),
                'note' => 'CSV application and premium records in the dataset.',
                'icon' => 'bi bi-file-earmark-text',
            ],
            [
                'label' => 'Application Premium Value',
                'value' => '$' . number_format($applicationAmount, 2),
                'note' => 'Amount represented by uploaded application records.',
                'icon' => 'bi bi-graph-up-arrow',
            ],
            [
                'label' => 'Face Value Stock Balance',
                'value' => number_format($faceValueBalance, 2),
                'note' => 'Current supervisor-level stock still in the system.',
                'icon' => 'bi bi-stack',
            ],
        ];

        $reportCards = [
            [
                'title' => 'Network Collection Report',
                'description' => 'Filter collections by network, site, and date range for operational review.',
                'route' => route('collectionreports'),
                'icon' => 'bi bi-globe2',
                'chip' => 'Collections',
            ],
            [
                'title' => 'Site Collection Report',
                'description' => 'Inspect detailed USD and ZWG activity across selected sites.',
                'route' => route('bysitesreports'),
                'icon' => 'bi bi-building',
                'chip' => 'Site detail',
            ],
            [
                'title' => 'Cumulative Network Report',
                'description' => 'Summarize transactions, premiums, and deposits by network over a period.',
                'route' => route('cumulativeNetworkReport'),
                'icon' => 'bi bi-bar-chart-line',
                'chip' => 'Cumulative',
            ],
            [
                'title' => 'User Performance Report',
                'description' => 'Break down collection activity for an individual user over time and by site.',
                'route' => route('user.reports'),
                'icon' => 'bi bi-person-lines-fill',
                'chip' => 'User focus',
            ],
            [
                'title' => 'Face Value Client History',
                'description' => 'Review face value allocations and balances across clerks.',
                'route' => route('clientfvreport'),
                'icon' => 'bi bi-upc-scan',
                'chip' => 'Face values',
            ],
            [
                'title' => 'Face Value Cumulative Report',
                'description' => 'Summarize opening, received, used, spoiled, and closing positions across a period.',
                'route' => route('cumulativefvreport'),
                'icon' => 'bi bi-archive',
                'chip' => 'Stock history',
            ],
            [
                'title' => 'Application Premium Report',
                'description' => 'Analyse uploaded application and premium records by status, agent, classification, and location.',
                'route' => route('reports.applications'),
                'icon' => 'bi bi-file-earmark-bar-graph',
                'chip' => 'CSV data',
            ],
            [
                'title' => 'Site and Network Change Requests',
                'description' => 'Trace pending, approved, and rejected site or network information changes.',
                'route' => route('master-data-change-requests.index'),
                'icon' => 'bi bi-shield-check',
                'chip' => 'Approvals',
            ],
            [
                'title' => 'Budget Comparison',
                'description' => 'Compare planned network budgets against actual collection figures.',
                'route' => route('budgets.index'),
                'icon' => 'bi bi-bank',
                'chip' => 'Budget vs actual',
            ],
            [
                'title' => 'Regional Network Report',
                'description' => 'View collection performance by Region and connected Office nodes.',
                'route' => route('reports.sbu'),
                'icon' => 'bi bi-building',
                'chip' => 'Regional Network',
            ],
        ];

        if (in_array($roleId, [3, 5], true)) {
            $reportCards[] = [
                'title' => 'Courier Sales Report',
                'description' => 'Review all Courier Connect sales, face values used, batches, clerks, and insurers.',
                'route' => $roleId === 5 ? route('courier.sales.superuser') : route('courier.sales.supervisor'),
                'icon' => 'bi bi-receipt',
                'chip' => 'Courier sales',
            ];
        }

        if ($roleId === 3) {
            $reportCards[] = [
                'title' => 'Only Sales Period Report',
                'description' => 'Review sales-only Zinara, premium, insurance, and total transaction calculations over a date range.',
                'route' => route('reports.only-sales.period'),
                'icon' => 'bi bi-receipt',
                'chip' => 'Sales only',
            ];

            $reportCards[] = [
                'title' => 'Only Sales Daily Report',
                'description' => 'Review sales-only calculations for a selected day, grouped by site.',
                'route' => route('reports.only-sales.daily'),
                'icon' => 'bi bi-calendar-day',
                'chip' => 'Daily sales',
            ];

            $reportCards[] = [
                'title' => 'Only Cash Period Report',
                'description' => 'Review cash, swipe, transfer, M-POS, receipt, balance, and deposit totals over a date range.',
                'route' => route('reports.only-cash.period'),
                'icon' => 'bi bi-cash-stack',
                'chip' => 'Cash only',
            ];

            $reportCards[] = [
                'title' => 'Only Cash Daily Report',
                'description' => 'Review cash receipt calculations for a selected day across all collection reports.',
                'route' => route('reports.only-cash.daily'),
                'icon' => 'bi bi-calendar-check',
                'chip' => 'Daily cash',
            ];
        }

        $operationalHighlights = [
            'sites' => number_format($collectionWindow->pluck('siteid')->filter()->unique()->count()),
            'users' => number_format($collectionWindow->pluck('user_id')->filter()->unique()->count()),
            'networks' => number_format($collectionWindow->pluck('networkid')->filter()->unique()->count()),
            'budgets' => number_format(Schema::hasTable('budgets') ? Budget::count() : 0),
            'face_values' => number_format(Schema::hasTable('face_values') ? FaceValue::count() : 0),
            'users_total' => number_format(Schema::hasTable('users') ? User::count() : 0),
        ];

        return view('Reports.hub', compact(
            'summaryCards',
            'reportCards',
            'collectionTrendLabels',
            'collectionTrendUsd',
            'collectionTrendZwg',
            'statusBreakdown',
            'locationBreakdown',
            'operationalHighlights',
            'userSBU'
        ));
    }

    private function courierSupervisorIndex()
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $userSBU = self::COURIER_PLATFORM;
        $visibleSiteIds = $this->getCourierSupervisorSiteIds();
        $authorizedClerkIds = $this->getCourierSupervisorAuthorizedClerkIds();
        $siteScope = $visibleSiteIds->isNotEmpty() ? $visibleSiteIds : [-1];
        $clerkScope = !empty($authorizedClerkIds) ? $authorizedClerkIds : [-1];
        $reportWindowStart = Carbon::now()->subDays(30);

        $collectionWindow = Schema::hasTable('daily_collections')
            ? DailyCollection::query()
                ->whereIn('user_id', $clerkScope)
                ->where('created_at', '>=', $reportWindowStart)
                ->get()
            : collect();

        $collectionTrendRows = Schema::hasTable('daily_collections')
            ? DB::table('daily_collections')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(insurance_transactions) as total_usd'),
                    DB::raw('SUM(zwg_insurance_transactions) as total_zwg')
                )
                ->whereIn('user_id', $clerkScope)
                ->where('created_at', '>=', $reportWindowStart)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy(DB::raw('DATE(created_at)'))
                ->get()
            : collect();

        $collectionTrendLabels = $collectionTrendRows->pluck('date')->toArray();
        $collectionTrendUsd = $collectionTrendRows->pluck('total_usd')->map(fn ($value) => (float) $value)->toArray();
        $collectionTrendZwg = $collectionTrendRows->pluck('total_zwg')->map(fn ($value) => (float) $value)->toArray();

        $csvRows = Schema::hasTable('csv_data') ? CsvData::all() : collect();
        $applicationCount = $csvRows->count();
        $applicationAmount = $csvRows->sum(fn ($row) => $this->parseCsvAmount($row->amount));

        $faceValueBalance = Schema::hasTable('supervisor_facevalue')
            ? Supervisorfacevalues::query()
                ->where(function ($query) use ($clerkScope, $user) {
                    $query->whereIn('assigned_to', $clerkScope)
                        ->orWhere('user_id', $user->id);
                })
                ->sum('balance')
            : 0;

        $statusBreakdown = $csvRows
            ->groupBy(fn ($row) => $row->status ?: 'Unknown')
            ->map(fn ($rows, $status) => [
                'label' => $status,
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        $locationBreakdown = $csvRows
            ->groupBy(fn ($row) => $row->location ?: 'Unknown')
            ->map(fn ($rows, $location) => [
                'label' => $location,
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        $pendingAmendments = Schema::hasTable('collection_ammendments')
            ? CollectionAmmendments::query()
                ->whereIn('userid', $clerkScope)
                ->where('status', 'requested')
                ->count()
            : 0;

        $pendingMasterDataChanges = Schema::hasTable('master_data_change_requests')
            ? MasterDataChangeRequest::query()
                ->where('status', 'pending')
                ->where('requested_by', $user->id)
                ->count()
            : 0;

        $summaryCards = [
            [
                'label' => 'USD Collections (30 Days)',
                'value' => '$' . number_format($collectionWindow->sum('insurance_transactions'), 2),
                'note' => "Combined submitted USD activity in the last 30 days. (Platform: $userSBU)",
                'icon' => 'bi bi-cash-coin',
            ],
            [
                'label' => 'ZWG Collections (30 Days)',
                'value' => 'ZWG ' . number_format($collectionWindow->sum('zwg_insurance_transactions'), 2),
                'note' => "Local currency collection movement in the last 30 days. (Platform: $userSBU)",
                'icon' => 'bi bi-wallet2',
            ],
            [
                'label' => 'Pending Amendments',
                'value' => number_format($pendingAmendments),
                'note' => 'Collection changes waiting review.',
                'icon' => 'bi bi-pencil-square',
            ],
            [
                'label' => 'Pending Site/Network Changes',
                'value' => number_format($pendingMasterDataChanges),
                'note' => 'Your submitted site/network changes waiting approval.',
                'icon' => 'bi bi-shield-check',
            ],
            [
                'label' => 'Imported Applications',
                'value' => number_format($applicationCount),
                'note' => 'CSV application and premium records in the dataset.',
                'icon' => 'bi bi-file-earmark-text',
            ],
            [
                'label' => 'Application Premium Value',
                'value' => '$' . number_format($applicationAmount, 2),
                'note' => 'Amount represented by uploaded application records.',
                'icon' => 'bi bi-graph-up-arrow',
            ],
            [
                'label' => 'Face Value Stock Balance',
                'value' => number_format($faceValueBalance, 2),
                'note' => 'Current supervisor-level stock still in the system.',
                'icon' => 'bi bi-stack',
            ],
        ];

        $reportCards = [
            [
                'title' => 'Network Collection Report',
                'description' => 'Filter Courier Connect collections by network, site, and date range for operational review.',
                'route' => route('collectionreports'),
                'icon' => 'bi bi-globe2',
                'chip' => 'Collections',
            ],
            [
                'title' => 'Site Collection Report',
                'description' => 'Inspect detailed USD and ZWG activity across Courier Connect sites.',
                'route' => route('bysitesreports'),
                'icon' => 'bi bi-building',
                'chip' => 'Site detail',
            ],
            [
                'title' => 'Cumulative Network Report',
                'description' => 'Summarize Courier transactions, premiums, and deposits by network over a period.',
                'route' => route('cumulativeNetworkReport'),
                'icon' => 'bi bi-bar-chart-line',
                'chip' => 'Cumulative',
            ],
            [
                'title' => 'User Performance Report',
                'description' => 'Break down collection activity for an individual Courier clerk.',
                'route' => route('user.reports'),
                'icon' => 'bi bi-person-lines-fill',
                'chip' => 'User focus',
            ],
            [
                'title' => 'Face Value Client History',
                'description' => 'Review Courier face value allocations and balances across clerks.',
                'route' => route('clientfvreport'),
                'icon' => 'bi bi-upc-scan',
                'chip' => 'Face values',
            ],
            [
                'title' => 'Face Value Cumulative Report',
                'description' => 'Summarize opening, received, used, spoiled, and closing positions across a period.',
                'route' => route('cumulativefvreport'),
                'icon' => 'bi bi-archive',
                'chip' => 'Stock history',
            ],
            [
                'title' => 'Courier Connect Performance Report',
                'description' => 'View collection performance for Courier Connect sites.',
                'route' => route('reports.sbu'),
                'icon' => 'bi bi-building',
                'chip' => 'Regional Network',
            ],
            [
                'title' => 'Only Sales Period Report',
                'description' => 'Review sales-only Zinara, premium, insurance, and total transaction calculations over a date range.',
                'route' => route('reports.only-sales.period'),
                'icon' => 'bi bi-receipt',
                'chip' => 'Sales only',
            ],
            [
                'title' => 'Only Sales Daily Report',
                'description' => 'Review sales-only calculations for a selected day, grouped by Courier Connect site.',
                'route' => route('reports.only-sales.daily'),
                'icon' => 'bi bi-calendar-day',
                'chip' => 'Daily sales',
            ],
            [
                'title' => 'Only Cash Period Report',
                'description' => 'Review cash, swipe, transfer, M-POS, receipt, balance, and deposit totals over a date range.',
                'route' => route('reports.only-cash.period'),
                'icon' => 'bi bi-cash-stack',
                'chip' => 'Cash only',
            ],
            [
                'title' => 'Only Cash Daily Report',
                'description' => 'Review cash receipt calculations for a selected day across Courier collection reports.',
                'route' => route('reports.only-cash.daily'),
                'icon' => 'bi bi-calendar-check',
                'chip' => 'Daily cash',
            ],
        ];

        $operationalHighlights = [
            'sites' => number_format($collectionWindow->pluck('siteid')->filter()->unique()->count()),
            'users' => number_format($collectionWindow->pluck('user_id')->filter()->unique()->count()),
            'networks' => number_format($collectionWindow->pluck('networkid')->filter()->unique()->count()),
            'budgets' => number_format(Schema::hasTable('budgets') ? Budget::count() : 0),
            'face_values' => number_format(Schema::hasTable('face_values') ? FaceValue::whereIn('clerk_id', $clerkScope)->count() : 0),
            'users_total' => number_format(Schema::hasTable('users') ? User::whereIn('id', $clerkScope)->count() : 0),
        ];

        return view('Reports.hub', compact(
            'summaryCards',
            'reportCards',
            'collectionTrendLabels',
            'collectionTrendUsd',
            'collectionTrendZwg',
            'statusBreakdown',
            'locationBreakdown',
            'operationalHighlights',
            'userSBU'
        ));
    }

    /**
     * Regional Network Report - Shows performance by Region.
     */
    public function sbuReport(Request $request)
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;

        if ($this->isCourierSupervisorRole($roleId)) {
            return $this->courierSupervisorSbuReport($request);
        }

        $userRegion = $this->getUserRegion();
        $visibleRegionIds = in_array($roleId, [3, 4, 8], true) ? $this->getVisibleRegionIdsForReports() : [];
        $isRegionalManager = in_array($roleId, [3, 4, 8], true);
        $authorizedClerkIds = $this->getAuthorizedClerkIds();
        
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now();
        
        $query = DailyCollection::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($isRegionalManager && !empty($authorizedClerkIds)) {
            $query->whereIn('user_id', $authorizedClerkIds);
        }
        
        $regionQuery = Network::query()->withCount(['offices as site_count']);
        
        if (!empty($visibleRegionIds)) {
            $regionQuery->whereIn('id', $visibleRegionIds);
        }
        
        $regions = $regionQuery->orderBy('name')->get();
        
        $sbuReports = [];
        
        foreach ($regions as $region) {
            $siteIds = site::where('network_id', $region->id)->pluck('id')->toArray();
            
            if (empty($siteIds)) {
                continue;
            }
            
            $regionData = (clone $query)
                ->whereIn('siteid', $siteIds)
                ->get();
            
            $sbuReports[] = [
                'region_id' => $region->id,
                'region' => $region->name,
                'usd_total' => $regionData->sum('insurance_transactions'),
                'zwg_total' => $regionData->sum('zwg_insurance_transactions'),
                'site_count' => count($siteIds),
                'active_sites' => $regionData->pluck('siteid')->unique()->count(),
                'transaction_count' => $regionData->count(),
                'usd_cash' => $regionData->sum('usd_cash'),
                'usd_swipe' => $regionData->sum('usd_swipe'),
                'usd_transfers' => $regionData->sum('usd_transfers'),
                'zwg_cash' => $regionData->sum('zwg_cash'),
                'zwg_swipe' => $regionData->sum('zwg_swipe'),
                'zwg_transfers' => $regionData->sum('zwg_transfers'),
            ];
        }
        
        // Sort by USD total descending
        $sbuReports = collect($sbuReports)->sortByDesc('usd_total')->values();
        
        // Calculate totals
        $totalUsd = $sbuReports->sum('usd_total');
        $totalZwg = $sbuReports->sum('zwg_total');
        
        $summaryCards = [
            [
                'label' => 'Total USD Collections',
                'value' => '$' . number_format($totalUsd, 2),
                'note' => 'Combined USD across all accessible Regional Networks',
                'icon' => 'bi bi-cash-coin',
            ],
            [
                'label' => 'Total ZWG Collections',
                'value' => 'ZWG ' . number_format($totalZwg, 2),
                'note' => 'Combined ZWG across all accessible Regional Networks',
                'icon' => 'bi bi-wallet2',
            ],
            [
                'label' => 'Active Regional Networks',
                'value' => number_format($sbuReports->where('usd_total', '>', 0)->count()),
                'note' => 'Regions with collection activity',
                'icon' => 'bi bi-building',
            ],
            [
                'label' => 'Connected Office Nodes',
                'value' => number_format($sbuReports->sum('site_count')),
                'note' => 'All offices in accessible Regions',
                'icon' => 'bi bi-pin-map',
            ],
        ];
        
        return view('Reports.sbu', compact('sbuReports', 'summaryCards', 'startDate', 'endDate', 'userRegion'));
    }

    private function courierSupervisorSbuReport(Request $request)
    {
        $userRegion = self::COURIER_PLATFORM;
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now();
        $siteIds = $this->getCourierSupervisorSiteIds()->toArray();
        $clerkIds = $this->getCourierSupervisorAuthorizedClerkIds();

        $sbuData = DailyCollection::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('user_id', !empty($clerkIds) ? $clerkIds : [-1])
            ->get();

        $sbuReports = collect([
            [
                'region_id' => self::COURIER_PLATFORM,
                'region' => self::COURIER_PLATFORM,
                'usd_total' => $sbuData->sum('insurance_transactions'),
                'zwg_total' => $sbuData->sum('zwg_insurance_transactions'),
                'site_count' => count($siteIds),
                'active_sites' => $sbuData->pluck('siteid')->unique()->count(),
                'transaction_count' => $sbuData->count(),
                'usd_cash' => $sbuData->sum('usd_cash'),
                'usd_swipe' => $sbuData->sum('usd_swipe'),
                'usd_transfers' => $sbuData->sum('usd_transfers'),
                'zwg_cash' => $sbuData->sum('zwg_cash'),
                'zwg_swipe' => $sbuData->sum('zwg_swipe'),
                'zwg_transfers' => $sbuData->sum('zwg_transfers'),
            ],
        ]);

        $summaryCards = [
            [
                'label' => 'Total USD Collections',
                'value' => '$' . number_format($sbuReports->sum('usd_total'), 2),
                'note' => 'Combined USD across Courier Connect sites',
                'icon' => 'bi bi-cash-coin',
            ],
            [
                'label' => 'Total ZWG Collections',
                'value' => 'ZWG ' . number_format($sbuReports->sum('zwg_total'), 2),
                'note' => 'Combined ZWG across Courier Connect sites',
                'icon' => 'bi bi-wallet2',
            ],
            [
                'label' => 'Active Regional Networks',
                'value' => number_format($sbuReports->where('usd_total', '>', 0)->count()),
                'note' => 'Courier Connect platform activity',
                'icon' => 'bi bi-building',
            ],
            [
                'label' => 'Total Sites',
                'value' => number_format($sbuReports->sum('site_count')),
                'note' => 'All Courier Connect sites',
                'icon' => 'bi bi-pin-map',
            ],
        ];

        return view('Reports.sbu', compact('sbuReports', 'summaryCards', 'startDate', 'endDate', 'userRegion'));
    }

    public function platformReport(Request $request)
    {
        $roleId = (int) Auth::user()->role_id;

        abort_unless(in_array($roleId, [1, 3, 4, 5], true), 403);

        $startDate = $request->filled('startdate') ? Carbon::parse($request->input('startdate'))->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->filled('enddate') ? Carbon::parse($request->input('enddate'))->endOfDay() : Carbon::now()->endOfDay();

        $query = DailyCollection::whereBetween('created_at', [$startDate, $endDate]);

        $platformReports = $query
            ->whereNotNull('platform_name')
            ->where('platform_name', '!=', '')
            ->select(
                'platform_name',
                DB::raw('SUM(insurance_transactions) as usd_total'),
                DB::raw('SUM(zwg_insurance_transactions) as zwg_total'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('platform_name')
            ->orderByDesc('usd_total')
            ->get();

        return view('Reports.platform', compact('platformReports', 'startDate', 'endDate'));
    }

    /**
     * Regional Network Detail Report - Shows offices within a specific Region.
     */
    public function sbuDetailReport(Request $request, $regionId)
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;

        if ($this->isCourierSupervisorRole($roleId)) {
            return $this->courierSupervisorSbuDetailReport($request, $regionId);
        }

        $userRegion = $this->getUserRegion();
        $visibleRegionIds = in_array($roleId, [3, 4, 8], true) ? $this->getVisibleRegionIdsForReports() : [];

        if (!empty($visibleRegionIds) && !in_array((int) $regionId, $visibleRegionIds, true)) {
            abort(403, 'You do not have access to this Regional Network report.');
        }

        $region = Network::findOrFail($regionId);
        
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now();
        
        $sites = site::where('network_id', $region->id)->get();
        $siteIds = $sites->pluck('id')->toArray();
        
        $query = DailyCollection::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('siteid', $siteIds);
        
        // Apply user filter for supervisors only
        
        $siteReports = [];
        
        foreach ($sites as $site) {
            $siteData = (clone $query)
                ->where('siteid', $site->id)
                ->get();
            
            $siteReports[] = [
                'site_name' => $site->site_name,
                'site_code' => $site->code,
                'network' => $site->network->name ?? 'N/A',
                'platform' => $site->platform_name ?? 'N/A',
                'usd_total' => $siteData->sum('insurance_transactions'),
                'zwg_total' => $siteData->sum('zwg_insurance_transactions'),
                'transaction_count' => $siteData->count(),
                'usd_cash' => $siteData->sum('usd_cash'),
                'usd_swipe' => $siteData->sum('usd_swipe'),
                'usd_transfers' => $siteData->sum('usd_transfers'),
                'zwg_cash' => $siteData->sum('zwg_cash'),
                'zwg_swipe' => $siteData->sum('zwg_swipe'),
                'zwg_transfers' => $siteData->sum('zwg_transfers'),
            ];
        }
        
        // Sort by USD total descending
        $siteReports = collect($siteReports)->sortByDesc('usd_total')->values();
        
        $totalUsd = $siteReports->sum('usd_total');
        $totalZwg = $siteReports->sum('zwg_total');
        
        $summaryCards = [
            [
                'label' => 'Total USD Collections',
                'value' => '$' . number_format($totalUsd, 2),
                'note' => "Total USD for {$region->name}",
                'icon' => 'bi bi-cash-coin',
            ],
            [
                'label' => 'Total ZWG Collections',
                'value' => 'ZWG ' . number_format($totalZwg, 2),
                'note' => "Total ZWG for {$region->name}",
                'icon' => 'bi bi-wallet2',
            ],
            [
                'label' => 'Active Office Nodes',
                'value' => number_format($siteReports->where('usd_total', '>', 0)->count()),
                'note' => 'Offices with collection activity',
                'icon' => 'bi bi-building',
            ],
            [
                'label' => 'Connected Office Nodes',
                'value' => number_format($siteReports->count()),
                'note' => 'All offices in this Region',
                'icon' => 'bi bi-pin-map',
            ],
        ];
        
        $regionName = $region->name;

        return view('Reports.sbu-detail', compact('siteReports', 'summaryCards', 'startDate', 'endDate', 'regionName', 'userRegion'));
    }

    private function courierSupervisorSbuDetailReport(Request $request, $sbu)
    {
        if (strtoupper(trim((string) $sbu)) !== strtoupper(self::COURIER_PLATFORM)) {
            abort(403, 'You do not have access to this Courier Connect report.');
        }

        $userRegion = self::COURIER_PLATFORM;
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now();
        $siteIds = $this->getCourierSupervisorSiteIds();
        $clerkIds = $this->getCourierSupervisorAuthorizedClerkIds();
        $sites = site::whereIn('id', $siteIds->isNotEmpty() ? $siteIds : [-1])->get();
        $siteIds = $sites->pluck('id')->toArray();
        $query = DailyCollection::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('user_id', !empty($clerkIds) ? $clerkIds : [-1]);

        $siteReports = [];

        foreach ($sites as $site) {
            $siteData = (clone $query)
                ->where('siteid', $site->id)
                ->get();

            $siteReports[] = [
                'site_name' => $site->site_name,
                'site_code' => $site->code,
                'network' => $site->network->name ?? 'N/A',
                'platform' => $site->platform_name ?? 'N/A',
                'usd_total' => $siteData->sum('insurance_transactions'),
                'zwg_total' => $siteData->sum('zwg_insurance_transactions'),
                'transaction_count' => $siteData->count(),
                'usd_cash' => $siteData->sum('usd_cash'),
                'usd_swipe' => $siteData->sum('usd_swipe'),
                'usd_transfers' => $siteData->sum('usd_transfers'),
                'zwg_cash' => $siteData->sum('zwg_cash'),
                'zwg_swipe' => $siteData->sum('zwg_swipe'),
                'zwg_transfers' => $siteData->sum('zwg_transfers'),
            ];
        }

        $siteReports = collect($siteReports)->sortByDesc('usd_total')->values();
        $totalUsd = $siteReports->sum('usd_total');
        $totalZwg = $siteReports->sum('zwg_total');

        $summaryCards = [
            [
                'label' => 'Total USD Collections',
                'value' => '$' . number_format($totalUsd, 2),
                'note' => 'Combined USD for Courier Connect sites',
                'icon' => 'bi bi-cash-coin',
            ],
            [
                'label' => 'Total ZWG Collections',
                'value' => 'ZWG ' . number_format($totalZwg, 2),
                'note' => 'Combined ZWG for Courier Connect sites',
                'icon' => 'bi bi-wallet2',
            ],
            [
                'label' => 'Active Sites',
                'value' => number_format($siteReports->where('usd_total', '>', 0)->count()),
                'note' => 'Sites with collection activity',
                'icon' => 'bi bi-building',
            ],
            [
                'label' => 'Total Transactions',
                'value' => number_format($siteReports->sum('transaction_count')),
                'note' => 'Collection submissions in selected period',
                'icon' => 'bi bi-list-check',
            ],
        ];

        $regionName = self::COURIER_PLATFORM;

        return view('Reports.sbu-detail', compact('siteReports', 'summaryCards', 'startDate', 'endDate', 'regionName', 'userRegion'));
    }

    public function applicationReports(Request $request)
    {
        if (!Schema::hasTable('csv_data')) {
            return view('Reports.applications', [
                'records' => collect(),
                'statusOptions' => collect(),
                'classificationOptions' => collect(),
                'insuranceTypeOptions' => collect(),
                'applicationSummary' => [],
                'statusChart' => collect(),
                'classificationChart' => collect(),
                'topAgents' => collect(),
                'topLocations' => collect(),
            ]);
        }

        $records = CsvData::query()
            ->when($request->filled('agent'), fn ($query) => $query->where('agent', 'like', '%' . $request->agent . '%'))
            ->when($request->filled('location'), fn ($query) => $query->where('location', 'like', '%' . $request->location . '%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('classification'), fn ($query) => $query->where('classification', $request->classification))
            ->when($request->filled('insurance_type'), fn ($query) => $query->where('insurance_type', $request->insurance_type))
            ->orderByDesc('created_at')
            ->get();

        if ($request->filled('startdate') || $request->filled('enddate')) {
            $startDate = $request->filled('startdate') ? Carbon::parse($request->startdate)->startOfDay() : null;
            $endDate = $request->filled('enddate') ? Carbon::parse($request->enddate)->endOfDay() : null;

            $records = $records->filter(function ($row) use ($startDate, $endDate) {
                $issueDate = $this->parseCsvDate($row->issue_date);
                if (!$issueDate) {
                    return false;
                }

                if ($startDate && $issueDate->lt($startDate)) {
                    return false;
                }

                if ($endDate && $issueDate->gt($endDate)) {
                    return false;
                }

                return true;
            })->values();
        }

        $statusOptions = CsvData::whereNotNull('status')->distinct()->orderBy('status')->pluck('status');
        $classificationOptions = CsvData::whereNotNull('classification')->distinct()->orderBy('classification')->pluck('classification');
        $insuranceTypeOptions = CsvData::whereNotNull('insurance_type')->distinct()->orderBy('insurance_type')->pluck('insurance_type');

        $applicationSummary = [
            [
                'label' => 'Applications',
                'value' => number_format($records->count()),
                'note' => 'Records matching the active filters.',
                'icon' => 'bi bi-file-earmark-text',
            ],
            [
                'label' => 'Premium Value',
                'value' => '$' . number_format($records->sum(fn ($row) => $this->parseCsvAmount($row->amount)), 2),
                'note' => 'Total amount represented by the filtered records.',
                'icon' => 'bi bi-cash-stack',
            ],
            [
                'label' => 'Active Agents',
                'value' => number_format($records->pluck('agent')->filter()->unique()->count()),
                'note' => 'Distinct agents represented in the current slice.',
                'icon' => 'bi bi-people',
            ],
            [
                'label' => 'Locations',
                'value' => number_format($records->pluck('location')->filter()->unique()->count()),
                'note' => 'Distinct locations represented in the current slice.',
                'icon' => 'bi bi-geo-alt',
            ],
        ];

        $statusChart = $records
            ->groupBy(fn ($row) => $row->status ?: 'Unknown')
            ->map(fn ($rows, $label) => ['label' => $label, 'count' => $rows->count()])
            ->sortByDesc('count')
            ->take(8)
            ->values();

        $classificationChart = $records
            ->groupBy(fn ($row) => $row->classification ?: 'Unknown')
            ->map(fn ($rows, $label) => ['label' => $label, 'count' => $rows->count()])
            ->sortByDesc('count')
            ->take(8)
            ->values();

        $topAgents = $records
            ->groupBy(fn ($row) => $row->agent ?: 'Unassigned')
            ->map(function ($rows, $agent) {
                return [
                    'agent' => $agent,
                    'count' => $rows->count(),
                    'amount' => $rows->sum(fn ($row) => $this->parseCsvAmount($row->amount)),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $topLocations = $records
            ->groupBy(fn ($row) => $row->location ?: 'Unknown')
            ->map(function ($rows, $location) {
                return [
                    'location' => $location,
                    'count' => $rows->count(),
                    'amount' => $rows->sum(fn ($row) => $this->parseCsvAmount($row->amount)),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return view('Reports.applications', compact(
            'records',
            'statusOptions',
            'classificationOptions',
            'insuranceTypeOptions',
            'applicationSummary',
            'statusChart',
            'classificationChart',
            'topAgents',
            'topLocations'
        ));
    }

    public function cumulativeNetworkReport(Request $request)
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;

        if ($this->isCourierSupervisorRole($roleId)) {
            return $this->courierSupervisorCumulativeNetworkReport($request);
        }

        $userSBU = $this->getReportScopeLabel();
        $isSupervisor = ($roleId == 3);
        $authorizedClerkIds = $this->getAuthorizedClerkIds();
        $visibleSbus = $this->getVisibleSbusForReports();
        
        $startdate = $request->startdate ?: Carbon::now()->startOfMonth()->toDateString();
        $enddate = $request->enddate ?: Carbon::now()->toDateString();
        $visibleSiteIds = collect();
        
        // Filter networks based on site.sbu if they are a supervisor.
        if ($isSupervisor && !empty($visibleSbus)) {
            $visibleSiteIds = site::query()
                ->whereIn(DB::raw("UPPER(REPLACE(sbu, ' ', ''))"), $visibleSbus)
                ->pluck('id');

            $networkIds = site::whereIn('id', $visibleSiteIds->isNotEmpty() ? $visibleSiteIds : [-1])
                ->pluck('network_id')
                ->filter()
                ->unique()
                ->values();

            $networks = Network::whereIn('id', $networkIds->isNotEmpty() ? $networkIds : [-1])->get();
        } else {
            $networks = Network::all();
        }

        $usdcompiledFigures = [];
        $zwgcompiledFigures = [];

        foreach ($networks as $network) {
            $query = DailyCollection::where('networkid', $network->id)
                ->whereBetween('created_at', [$startdate, $enddate]);
            
            // Filter by authorized clerks if supervisor
            if ($isSupervisor && !empty($authorizedClerkIds)) {
                $query->whereIn('user_id', $authorizedClerkIds);
            }

            if ($isSupervisor && $visibleSiteIds->isNotEmpty()) {
                $query->whereIn('siteid', $visibleSiteIds);
            }
            
            $sql = $query->get();

            $usdcompiledFigures[] = [
                'networkname' => $network->name,
                'insurance_transactions' => $sql->sum('insurance_transactions'),
                'zinara_transactions' => $sql->sum('zinara_fees'),
                'third_party_premiums' => $sql->sum('third_party_premiums'),
                'full_cover_premiums' => $sql->sum('full_cover_premiums'),
                'usd_total_deposited' => $sql->sum('usd_total_deposited'),
                'usd_cash' => $sql->sum('usd_cash'),
                'usd_swipe' => $sql->sum('usd_swipe'),
                'usd_transfers' => $sql->sum('usd_transfers'),
                'usd_debit_sales' => $sql->sum('usd_debit_sales'),
                'usd_credit_sales' => $sql->sum('usd_credit_sales'),
            ];

            $zwgcompiledFigures[] = [
                'networkname' => $network->name,
                'zwg_insurance_transactions' => $sql->sum('zwg_insurance_transactions'),
                'zwg_zinara_fees' => $sql->sum('zwg_zinara_fees'),
                'zwg_third_party_premiums' => $sql->sum('zwg_third_party_premiums'),
                'zwg_full_cover_premiums' => $sql->sum('zwg_full_cover_premiums'),
                'zwg_total_deposited' => $sql->sum('zwg_total_deposited'),
                'zwg_cash' => $sql->sum('zwg_cash'),
                'zwg_swipe' => $sql->sum('zwg_swipe'),
                'zwg_transfers' => $sql->sum('zwg_transfers'),
                'zwg_debit_sales' => $sql->sum('zwg_debit_sales'),
                'zwg_credit_sales' => $sql->sum('zwg_credit_sales'),
            ];
        }

        return view('Reports.cumulativenetwork', compact('networks', 'usdcompiledFigures', 'zwgcompiledFigures', 'startdate', 'enddate', 'userSBU'));
    }

    private function courierSupervisorCumulativeNetworkReport(Request $request)
    {
        $userSBU = self::COURIER_PLATFORM;
        $startdate = $request->startdate ?: Carbon::now()->startOfMonth()->toDateString();
        $enddate = $request->enddate ?: Carbon::now()->toDateString();
        $visibleSiteIds = $this->getCourierSupervisorSiteIds();
        $clerkIds = $this->getCourierSupervisorAuthorizedClerkIds();

        $networkIds = site::whereIn('id', $visibleSiteIds->isNotEmpty() ? $visibleSiteIds : [-1])
            ->pluck('network_id')
            ->filter()
            ->unique()
            ->values();

        $networks = Network::whereIn('id', $networkIds->isNotEmpty() ? $networkIds : [-1])->get();
        $usdcompiledFigures = [];
        $zwgcompiledFigures = [];

        foreach ($networks as $network) {
            $sql = DailyCollection::where('networkid', $network->id)
                ->whereBetween('created_at', [$startdate, $enddate])
                ->whereIn('user_id', !empty($clerkIds) ? $clerkIds : [-1])
                ->get();

            $usdcompiledFigures[] = [
                'networkname' => $network->name,
                'insurance_transactions' => $sql->sum('insurance_transactions'),
                'zinara_transactions' => $sql->sum('zinara_fees'),
                'third_party_premiums' => $sql->sum('third_party_premiums'),
                'full_cover_premiums' => $sql->sum('full_cover_premiums'),
                'usd_total_deposited' => $sql->sum('usd_total_deposited'),
                'usd_cash' => $sql->sum('usd_cash'),
                'usd_swipe' => $sql->sum('usd_swipe'),
                'usd_transfers' => $sql->sum('usd_transfers'),
                'usd_debit_sales' => $sql->sum('usd_debit_sales'),
                'usd_credit_sales' => $sql->sum('usd_credit_sales'),
            ];

            $zwgcompiledFigures[] = [
                'networkname' => $network->name,
                'zwg_insurance_transactions' => $sql->sum('zwg_insurance_transactions'),
                'zwg_zinara_fees' => $sql->sum('zwg_zinara_fees'),
                'zwg_third_party_premiums' => $sql->sum('zwg_third_party_premiums'),
                'zwg_full_cover_premiums' => $sql->sum('zwg_full_cover_premiums'),
                'zwg_total_deposited' => $sql->sum('zwg_total_deposited'),
                'zwg_cash' => $sql->sum('zwg_cash'),
                'zwg_swipe' => $sql->sum('zwg_swipe'),
                'zwg_transfers' => $sql->sum('zwg_transfers'),
                'zwg_debit_sales' => $sql->sum('zwg_debit_sales'),
                'zwg_credit_sales' => $sql->sum('zwg_credit_sales'),
            ];
        }

        return view('Reports.cumulativenetwork', compact('networks', 'usdcompiledFigures', 'zwgcompiledFigures', 'startdate', 'enddate', 'userSBU'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorereportsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(reports $reports)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(reports $reports)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatereportsRequest $request, reports $reports)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(reports $reports)
    {
        //
    }

    public function search(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $transactionType = $request->input('transaction_type');

        $reports = Report::filterByDate($startDate, $endDate)
            ->filterByType($transactionType)
            ->get();

        return view('Reports.results', compact('reports'));
    }

    public function downloadPDF(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $transactionType = $request->input('transaction_type');

        $reports = reports::filterByDate($startDate, $endDate)
            ->filterByType($transactionType)
            ->get();

        $pdf = PDF::loadView('Reports.pdf', compact('reports'));
        return $pdf->download('Report.pdf');
    }

    private function parseCsvAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    }

    private function parseCsvDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}

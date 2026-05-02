<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorereportsRequest;
use App\Http\Requests\UpdatereportsRequest;
use App\Models\Budget;
use App\Models\CollectionAmmendments;
use App\Models\CsvData;
use App\Models\DailyCollection;
use App\Models\FaceValue;
use App\Models\Network;
use App\Models\reports;
use App\Models\reports as Report;
use App\Models\Supervisorfacevalues;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportsController extends Controller
{
    private const NON_COURIER_SBUS = ['SBU1', 'SBU2'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function normalizeSbu(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim($value)));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Get the user's SBU based on their site or network
     */
    private function getUserSBU()
    {
        $user = Auth::user();
        
        if($user->site && $user->site->sbu) {
            return $this->normalizeSbu($user->site->sbu);
        }
        
        if($user->network && $user->network->name) {
            return $this->normalizeSbu($user->network->name);
        }
        
        return null;
    }

    private function getVisibleSbusForReports(): array
    {
        $userSBU = $this->getUserSBU();

        if (in_array($userSBU, self::NON_COURIER_SBUS, true)) {
            return self::NON_COURIER_SBUS;
        }

        return $userSBU ? [$userSBU] : [];
    }

    private function getReportScopeLabel(): ?string
    {
        $visibleSbus = $this->getVisibleSbusForReports();

        if ($visibleSbus === self::NON_COURIER_SBUS) {
            return 'SBU1 & SBU2';
        }

        return $visibleSbus[0] ?? null;
    }

    /**
     * Get authorized clerk IDs based on user's SBU
     */
    private function getAuthorizedClerkIds()
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $visibleSbus = $this->getVisibleSbusForReports();
        
        // For supervisors (role 3 or 6) with an SBU
        if (($roleId == 3 || $roleId == 6) && !empty($visibleSbus)) {
            $clerkIds = User::whereIn('role_id', [2, 7])
                ->where(function($query) use ($visibleSbus) {
                    $query->whereHas('network', function($q) use ($visibleSbus) {
                        $q->whereIn('name', $visibleSbus);
                    });
                    $query->orWhereHas('site', function($q) use ($visibleSbus) {
                        $q->whereIn('sbu', $visibleSbus);
                    });
                })
                ->pluck('id')
                ->toArray();
            
            return $clerkIds;
        }
        
        // For super admin or users without SBU, return empty (means no filter)
        return [];
    }

    /**
     * Display the reporting hub.
     */
    public function index()
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $userSBU = $this->getReportScopeLabel();
        $isSupervisor = ($roleId == 3 || $roleId == 6);
        $authorizedClerkIds = $this->getAuthorizedClerkIds();
        
        $reportWindowStart = Carbon::now()->subDays(30);
        
        // Filter daily collections based on user's SBU
        $collectionQuery = DailyCollection::query();
        if ($isSupervisor && $userSBU && !empty($authorizedClerkIds)) {
            $collectionQuery->whereIn('user_id', $authorizedClerkIds);
        }
        
        $collectionWindow = Schema::hasTable('daily_collections')
            ? $collectionQuery->where('created_at', '>=', $reportWindowStart)->get()
            : collect();

        // Filter collection trend based on user's SBU
        $collectionTrendQuery = DB::table('daily_collections')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(insurance_transactions) as total_usd'),
                DB::raw('SUM(zwg_insurance_transactions) as total_zwg')
            )
            ->where('created_at', '>=', $reportWindowStart);
            
        if ($isSupervisor && $userSBU && !empty($authorizedClerkIds)) {
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

        // Filter CSV data based on user's SBU (if applicable)
        $csvRows = Schema::hasTable('csv_data') ? CsvData::all() : collect();
        $applicationCount = $csvRows->count();
        $applicationAmount = $csvRows->sum(fn ($row) => $this->parseCsvAmount($row->amount));

        // Filter face value stock based on user's SBU
        $faceValueQuery = Supervisorfacevalues::query();
        if ($isSupervisor && $userSBU && !empty($authorizedClerkIds)) {
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

        // Filter pending amendments based on user's SBU
        $amendmentQuery = CollectionAmmendments::query();
        if ($isSupervisor && $userSBU && !empty($authorizedClerkIds)) {
            $amendmentQuery->whereIn('userid', $authorizedClerkIds);
        }
        
        $pendingAmendments = Schema::hasTable('collection_ammendments')
            ? $amendmentQuery->where('status', 'requested')->count()
            : 0;

        $summaryCards = [
            [
                'label' => 'USD Collections (30 Days)',
                'value' => '$' . number_format($collectionWindow->sum('insurance_transactions'), 2),
                'note' => 'Combined submitted USD activity in the last 30 days.' . ($userSBU ? " (SBU: $userSBU)" : ''),
                'icon' => 'bi bi-cash-coin',
            ],
            [
                'label' => 'ZWG Collections (30 Days)',
                'value' => 'ZWG ' . number_format($collectionWindow->sum('zwg_insurance_transactions'), 2),
                'note' => 'Local currency collection movement in the last 30 days.' . ($userSBU ? " (SBU: $userSBU)" : ''),
                'icon' => 'bi bi-wallet2',
            ],
            [
                'label' => 'Pending Amendments',
                'value' => number_format($pendingAmendments),
                'note' => 'Collection changes waiting review.',
                'icon' => 'bi bi-pencil-square',
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
                'title' => 'Budget Comparison',
                'description' => 'Compare planned network budgets against actual collection figures.',
                'route' => route('budgets.index'),
                'icon' => 'bi bi-bank',
                'chip' => 'Budget vs actual',
            ],
        ];

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
        $userSBU = $this->getReportScopeLabel();
        $isSupervisor = ($roleId == 3 || $roleId == 6);
        $authorizedClerkIds = $this->getAuthorizedClerkIds();
        $visibleSbus = $this->getVisibleSbusForReports();
        
        $startdate = $request->startdate ?: Carbon::now()->startOfMonth()->toDateString();
        $enddate = $request->enddate ?: Carbon::now()->toDateString();
        
        // Filter networks based on user's SBU if they are a supervisor
        if ($isSupervisor && !empty($visibleSbus)) {
            $networks = Network::whereIn('name', $visibleSbus)->get();
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

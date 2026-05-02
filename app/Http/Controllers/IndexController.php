<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DailyCollection;
use App\Models\CollectionAmmendments;
use App\Models\FaceValue;
use App\Models\Supervisorfacevalues;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Network;
use App\Models\site;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    private const REPORT_SBUS = ['SBU1', 'SBU2', 'SBU3'];
    private const NON_COURIER_REPORT_SBUS = ['SBU1', 'SBU2'];
    private const COMBINED_NON_COURIER_FILTER = 'SBU1_SBU2';

    private function normalizeReportSbu(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim($value)));

        return in_array($normalized, array_merge(self::REPORT_SBUS, [self::COMBINED_NON_COURIER_FILTER]), true)
            ? $normalized
            : null;
    }

    private function expandReportSbuFilter(?string $sbu)
    {
        $normalized = $this->normalizeReportSbu($sbu);

        if ($normalized === self::COMBINED_NON_COURIER_FILTER) {
            return collect(self::NON_COURIER_REPORT_SBUS);
        }

        return $normalized ? collect([$normalized]) : collect();
    }

    private function getSiteIdsForReportSbu(?string $sbu)
    {
        $normalizedSbus = $this->expandReportSbuFilter($sbu);

        if ($normalizedSbus->isEmpty()) {
            return collect();
        }

        return site::query()
            ->get(['id', 'sbu'])
            ->filter(fn ($site) => $normalizedSbus->contains($this->normalizeReportSbu($site->sbu)))
            ->pluck('id')
            ->values();
    }

    private function getReportUserSBU(): ?string
    {
        $user = Auth::user();

        if ($user?->site?->sbu) {
            return $this->normalizeReportSbu($user->site->sbu);
        }

        if ($user?->network?->name) {
            return $this->normalizeReportSbu($user->network->name);
        }

        return null;
    }

    private function isReportSupervisor(int $roleId): bool
    {
        return in_array($roleId, [3, 6], true);
    }

    private function hasGlobalReportAccess(int $roleId): bool
    {
        return in_array($roleId, [1, 5], true);
    }

    private function canUseSbuReportFilter(int $roleId): bool
    {
        return $this->isReportSupervisor($roleId) || $this->hasGlobalReportAccess($roleId);
    }

    private function resolvedReportSbu(Request $request): ?string
    {
        $roleId = (int) Auth::user()->role_id;

        if ($this->isReportSupervisor($roleId)) {
            $userSbu = $this->getReportUserSBU();

            if (in_array($userSbu, self::NON_COURIER_REPORT_SBUS, true)) {
                return self::COMBINED_NON_COURIER_FILTER;
            }

            return $userSbu;
        }

        if ($this->hasGlobalReportAccess($roleId)) {
            $selectedSbu = $this->normalizeReportSbu((string) $request->input('sbu'));

            return $selectedSbu;
        }

        return null;
    }

    private function applyCollectionReportScope($query, ?string $sbu = null)
    {
        $roleId = (int) Auth::user()->role_id;

        if ($sbu && $this->canUseSbuReportFilter($roleId)) {
            $siteIds = $this->getSiteIdsForReportSbu($sbu);
            $query->whereIn('siteid', $siteIds->isNotEmpty() ? $siteIds : [-1]);
        }

        return $query;
    }

    private function getCollectionReportSbuOptions(int $roleId, ?string $resolvedSbu)
    {
        if ($this->isReportSupervisor($roleId) && $resolvedSbu) {
            return collect([$resolvedSbu]);
        }

        return collect(self::REPORT_SBUS);
    }

    private function getCollectionReportNetworks(int $roleId, ?string $resolvedSbu)
    {
        $allowedSiteIds = null;

        if ($this->canUseSbuReportFilter($roleId) && $resolvedSbu) {
            $allowedSiteIds = $this->getSiteIdsForReportSbu($resolvedSbu);
        }

        $networkIds = site::query()
            ->when($allowedSiteIds !== null, fn ($query) => $query->whereIn('id', $allowedSiteIds->isNotEmpty() ? $allowedSiteIds : [-1]))
            ->pluck('network_id')
            ->filter()
            ->unique()
            ->values();

        return Network::query()
            ->when($networkIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $networkIds))
            ->orderBy('name')
            ->get();
    }

    public function index()
    {
        $userId = Auth::id();
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        $roleId = (int) $user->role_id;
        $isZINARASupervisor = ($roleId == 6);
        $isZINARAClerk = ($roleId == 7);
        $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
        $isElevated = in_array($roleId, [1, 3, 4, 5], true);
        $lastThirtyDays = Carbon::now()->subDays(30);

        // Initialize ZINARA variables
        $zinaraLineChartLabels = [];
        $zinaraUsedData = [];
        $zinaraSpoiledData = [];
        $zinaraPieLabels = [];
        $zinaraPieData = [];
        $clerkBalanceLabels = [];
        $clerkBalanceData = [];
        $recentDeclarations = collect();
        $lowStockAlerts = collect();

        // ZINARA-specific data for roles 6 and 7
        if ($isZINARAUser) {
            // Get recent declarations
            if ($isZINARASupervisor) {
                // Get clerks created by this ZINARA supervisor
                $authorizedClerkIds = User::where('role_id', 7)
                    ->where('name', $userId)
                    ->pluck('id')
                    ->toArray();
                
                $recentDeclarations = FaceValue::where('is_parent', false)
                    ->whereIn('clerk_id', $authorizedClerkIds)
                    ->with(['clerk', 'clerk.site'])
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($fv) {
                        return (object)[
                            'clerk_name' => $fv->clerk ? ($fv->clerk->name . ' ' . ($fv->clerk->surname ?? '')) : 'Unknown',
                            'site_name' => $fv->clerk && $fv->clerk->site ? $fv->clerk->site->site_name : 'N/A',
                            'batch_id' => $fv->batch_id,
                            'used' => $fv->used,
                            'spoiled' => $fv->spoiled,
                            'closing_balance' => $fv->closing_balance,
                            'created_at' => $fv->created_at,
                        ];
                    });
                
                // Get low stock alerts (balance < 20)
                $lowStockAlerts = FaceValue::where('is_parent', true)
                    ->whereIn('clerk_id', $authorizedClerkIds)
                    ->where('batch_balance', '<', 20)
                    ->where('batch_balance', '>', 0)
                    ->with(['clerk', 'clerk.site'])
                    ->get()
                    ->map(function ($fv) {
                        return (object)[
                            'clerk_name' => $fv->clerk ? ($fv->clerk->name . ' ' . ($fv->clerk->surname ?? '')) : 'Unknown',
                            'site_name' => $fv->clerk && $fv->clerk->site ? $fv->clerk->site->site_name : 'N/A',
                            'balance' => $fv->batch_balance,
                            'batch_id' => $fv->batch_id,
                            'last_activity' => $fv->updated_at ? $fv->updated_at->format('Y-m-d H:i') : 'N/A',
                        ];
                    });
            } elseif ($isZINARAClerk) {
                // ZINARA Clerk - only their own declarations
                $recentDeclarations = FaceValue::where('clerk_id', $userId)
                    ->where('is_parent', false)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($fv) use ($user) {
                        return (object)[
                            'clerk_name' => $user->name . ' ' . ($user->surname ?? ''),
                            'site_name' => $user->site ? $user->site->site_name : 'N/A',
                            'batch_id' => $fv->batch_id,
                            'used' => $fv->used,
                            'spoiled' => $fv->spoiled,
                            'closing_balance' => $fv->closing_balance,
                            'created_at' => $fv->created_at,
                        ];
                    });
            }
            
            // Get daily used and spoiled data for line chart
            $dailyUsedData = FaceValue::where('is_parent', false)
                ->when($isZINARASupervisor, function($query) use ($userId) {
                    $authorizedClerkIds = User::where('role_id', 7)
                        ->where('name', $userId)
                        ->pluck('id')
                        ->toArray();
                    return $query->whereIn('clerk_id', $authorizedClerkIds);
                })
                ->when($isZINARAClerk, function($query) use ($userId) {
                    return $query->where('clerk_id', $userId);
                })
                ->where('created_at', '>=', $lastThirtyDays)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(used) as total_used'), DB::raw('SUM(spoiled) as total_spoiled'))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            $usedDataMap = $dailyUsedData->keyBy('date');
            $allDates = $dailyUsedData->pluck('date')->unique()->sort()->values()->toArray();
            $zinaraLineChartLabels = $allDates;
            
            foreach ($zinaraLineChartLabels as $date) {
                $zinaraUsedData[] = $usedDataMap->has($date) ? (float) $usedDataMap[$date]->total_used : 0;
                $zinaraSpoiledData[] = $usedDataMap->has($date) ? (float) $usedDataMap[$date]->total_spoiled : 0;
            }
            
            // Get pie chart data (stock vs allocated vs used/spoiled)
            if ($isZINARASupervisor) {
                $authorizedClerkIds = User::where('role_id', 7)
                    ->where('name', $userId)
                    ->pluck('id')
                    ->toArray();
                
                $totalStock = FaceValue::where('is_parent', true)
                    ->whereIn('clerk_id', $authorizedClerkIds)
                    ->sum('received');
                
                $totalUsedSpoiled = FaceValue::where('is_parent', false)
                    ->whereIn('clerk_id', $authorizedClerkIds)
                    ->select(DB::raw('SUM(used) + SUM(spoiled) as total'))
                    ->value('total') ?? 0;
                
                $currentBalance = FaceValue::where('is_parent', true)
                    ->whereIn('clerk_id', $authorizedClerkIds)
                    ->sum('batch_balance');
                
                $zinaraPieLabels = ['In Stock', 'Used/Spoiled'];
                $zinaraPieData = [$currentBalance, $totalUsedSpoiled];
            } else {
                $totalStock = FaceValue::where('clerk_id', $userId)
                    ->where('is_parent', true)
                    ->sum('received');
                
                $totalUsedSpoiled = FaceValue::where('clerk_id', $userId)
                    ->where('is_parent', false)
                    ->select(DB::raw('SUM(used) + SUM(spoiled) as total'))
                    ->value('total') ?? 0;
                
                $currentBalance = FaceValue::where('clerk_id', $userId)
                    ->where('is_parent', true)
                    ->sum('batch_balance');
                
                $zinaraPieLabels = ['Your Stock', 'Used/Spoiled'];
                $zinaraPieData = [$currentBalance, $totalUsedSpoiled];
            }
            
            // Get clerk balances for bar chart (supervisors only)
            if ($isZINARASupervisor) {
                $authorizedClerkIds = User::where('role_id', 7)
                    ->where('name', $userId)
                    ->pluck('id')
                    ->toArray();
                
                $clerkBalances = FaceValue::where('is_parent', true)
                    ->whereIn('clerk_id', $authorizedClerkIds)
                    ->select('clerk_id', DB::raw('SUM(batch_balance) as total_balance'))
                    ->groupBy('clerk_id')
                    ->orderByDesc('total_balance')
                    ->limit(10)
                    ->get();
                
                $clerks = User::whereIn('id', $clerkBalances->pluck('clerk_id'))->get()->keyBy('id');
                
                foreach ($clerkBalances as $balance) {
                    $clerk = $clerks->get($balance->clerk_id);
                    $clerkBalanceLabels[] = $clerk ? ($clerk->name . ' ' . ($clerk->surname ?? '')) : 'Unknown';
                    $clerkBalanceData[] = (float) $balance->total_balance;
                }
            }
        }

        // Regular collections scope (non-ZINARA)
        $collectionsScope = DailyCollection::query();
        if (!$isElevated && !$isZINARAUser) {
            $collectionsScope->where('user_id', $userId);
        }

        $collectionWindow = (clone $collectionsScope)
            ->where('created_at', '>=', $lastThirtyDays)
            ->get();

        $lineChartLabels = [];
        $lineChartUsdData = [];
        $lineChartZwgData = [];
        $barChartLabels = [];
        $barChartUsdData = [];
        $barChartZwgData = [];
        $networkReportLabels = [];
        $networkReportUsdData = [];
        $networkReportZwgData = [];

        // Only get regular chart data for non-ZINARA users
        if (!$isZINARAUser) {
            $dailyUsdTransactionsForChart = DB::table('daily_collections')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(insurance_transactions) as total_transactions'))
                ->when(!$isElevated, fn($query) => $query->where('user_id', $userId))
                ->where('created_at', '>=', $lastThirtyDays)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $dailyZwgTransactionsForChart = DB::table('daily_collections')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(zwg_insurance_transactions) as total_transactions'))
                ->when(!$isElevated, fn($query) => $query->where('user_id', $userId))
                ->where('created_at', '>=', $lastThirtyDays)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $usdDataMap = $dailyUsdTransactionsForChart->keyBy('date');
            $zwgDataMap = $dailyZwgTransactionsForChart->keyBy('date');
            $allDates = array_unique(array_merge($usdDataMap->keys()->toArray(), $zwgDataMap->keys()->toArray()));
            sort($allDates);
            $lineChartLabels = $allDates;

            foreach ($lineChartLabels as $date) {
                $lineChartUsdData[] = $usdDataMap->has($date) ? (float) $usdDataMap[$date]->total_transactions : 0;
                $lineChartZwgData[] = $zwgDataMap->has($date) ? (float) $zwgDataMap[$date]->total_transactions : 0;
            }

            $siteTransactionsForChart = DB::table('daily_collections')
                ->select(
                    'site_name',
                    DB::raw('SUM(insurance_transactions) as total_usd_transactions'),
                    DB::raw('SUM(zwg_insurance_transactions) as total_zwg_transactions')
                )
                ->when(!$isElevated, fn($query) => $query->where('user_id', $userId))
                ->where('created_at', '>=', $lastThirtyDays)
                ->groupBy('site_name')
                ->orderByDesc(DB::raw('SUM(insurance_transactions) + SUM(zwg_insurance_transactions)'))
                ->limit(8)
                ->get();

            $barChartLabels = $siteTransactionsForChart->map(fn($row) => $row->site_name ?: 'Unassigned Site')->toArray();
            $barChartUsdData = $siteTransactionsForChart->pluck('total_usd_transactions')->map(fn($value) => (float) $value)->toArray();
            $barChartZwgData = $siteTransactionsForChart->pluck('total_zwg_transactions')->map(fn($value) => (float) $value)->toArray();

            $networkTransactions = DB::table('daily_collections')
                ->leftJoin('network', 'daily_collections.networkid', '=', 'network.id')
                ->select(
                    DB::raw("COALESCE(network.name, 'Unassigned Network') as network_name"),
                    DB::raw('SUM(daily_collections.insurance_transactions) as total_usd_transactions'),
                    DB::raw('SUM(daily_collections.zwg_insurance_transactions) as total_zwg_transactions')
                )
                ->when(!$isElevated, fn($query) => $query->where('daily_collections.user_id', $userId))
                ->where('daily_collections.created_at', '>=', $lastThirtyDays)
                ->groupBy('network.name')
                ->orderByDesc(DB::raw('SUM(daily_collections.insurance_transactions) + SUM(daily_collections.zwg_insurance_transactions)'))
                ->limit(8)
                ->get();

            $networkReportLabels = $networkTransactions->pluck('network_name')->toArray();
            $networkReportUsdData = $networkTransactions->pluck('total_usd_transactions')->map(fn($value) => (float) $value)->toArray();
            $networkReportZwgData = $networkTransactions->pluck('total_zwg_transactions')->map(fn($value) => (float) $value)->toArray();
        }

        $pendingAmendments = $isElevated && !$isZINARAUser
            ? CollectionAmmendments::where('status', 'requested')->count()
            : ($isZINARAUser ? 0 : CollectionAmmendments::where('userid', $userId)->where('status', 'requested')->count());

        $faceValueBalance = $roleId === 2 || $roleId === 7
            ? FaceValue::where('clerk_id', $userId)->sum('closing_balance')
            : Supervisorfacevalues::when($roleId === 3, fn($query) => $query->where('user_id', $userId))
                ->when($roleId === 6, fn($query) => $query->where('user_id', $userId))
                ->whereNull('batch_id')
                ->sum('balance');

        // Summary cards for ZINARA users
        if ($isZINARAUser) {
            $summaryCards = [
                [
                    'label' => 'Face Value Balance',
                    'value' => number_format($faceValueBalance, 0),
                    'note' => $isZINARAClerk ? 'Your remaining face value stock.' : 'Total face value stock across clerks.',
                    'icon' => 'bi bi-upc-scan',
                ],
                [
                    'label' => 'Used This Month',
                    'value' => number_format(FaceValue::where('is_parent', false)
                        ->when($isZINARASupervisor, function($query) use ($userId) {
                            $authorizedClerkIds = User::where('role_id', 7)->where('name', $userId)->pluck('id')->toArray();
                            return $query->whereIn('clerk_id', $authorizedClerkIds);
                        })
                        ->when($isZINARAClerk, fn($query) => $query->where('clerk_id', $userId))
                        ->whereMonth('created_at', Carbon::now()->month)
                        ->sum('used'), 0),
                    'note' => 'Face values declared as used this month.',
                    'icon' => 'bi bi-check-circle',
                ],
                [
                    'label' => 'Spoiled This Month',
                    'value' => number_format(FaceValue::where('is_parent', false)
                        ->when($isZINARASupervisor, function($query) use ($userId) {
                            $authorizedClerkIds = User::where('role_id', 7)->where('name', $userId)->pluck('id')->toArray();
                            return $query->whereIn('clerk_id', $authorizedClerkIds);
                        })
                        ->when($isZINARAClerk, fn($query) => $query->where('clerk_id', $userId))
                        ->whereMonth('created_at', Carbon::now()->month)
                        ->sum('spoiled'), 0),
                    'note' => 'Face values marked as spoiled this month.',
                    'icon' => 'bi bi-exclamation-triangle',
                ],
                [
                    'label' => $isZINARASupervisor ? 'Active Clerks' : 'Declarations',
                    'value' => $isZINARASupervisor 
                        ? number_format(User::where('role_id', 7)->where('name', $userId)->count())
                        : number_format(FaceValue::where('clerk_id', $userId)->where('is_parent', false)->count()),
                    'note' => $isZINARASupervisor ? 'ZINARA clerks under your supervision.' : 'Total declarations submitted.',
                    'icon' => $isZINARASupervisor ? 'bi bi-people' : 'bi bi-journal-text',
                ],
            ];
        } else {
            $summaryCards = [
                [
                    'label' => 'USD Collections (30 Days)',
                    'value' => '$' . number_format($collectionWindow->sum('insurance_transactions'), 2),
                    'note' => 'Premiums and fees submitted in your reporting scope.',
                    'icon' => 'bi bi-cash-coin',
                ],
                [
                    'label' => 'ZWG Collections (30 Days)',
                    'value' => 'ZWG ' . number_format($collectionWindow->sum('zwg_insurance_transactions'), 2),
                    'note' => 'Local currency activity captured over the last month.',
                    'icon' => 'bi bi-wallet2',
                ],
                [
                    'label' => 'Submissions Today',
                    'value' => number_format((clone $collectionsScope)->whereDate('created_at', Carbon::today())->count()),
                    'note' => $isElevated ? 'Entries across the current oversight area.' : 'Collections you recorded today.',
                    'icon' => 'bi bi-journal-check',
                ],
                [
                    'label' => $roleId === 2 ? 'Face Value Balance' : 'Pending Amendments',
                    'value' => $roleId === 2 ? number_format($faceValueBalance, 2) : number_format($pendingAmendments),
                    'note' => $roleId === 2 ? 'Outstanding stock assigned to your workstation.' : 'Requests still waiting for review.',
                    'icon' => $roleId === 2 ? 'bi bi-upc-scan' : 'bi bi-pencil-square',
                ],
            ];
        }

        $dashboardSpotlights = [
            [
                'label' => 'Active Sites',
                'value' => number_format((clone $collectionsScope)->where('created_at', '>=', $lastThirtyDays)->distinct('siteid')->count('siteid')),
            ],
            [
                'label' => 'Networks In View',
                'value' => $isElevated ? number_format(Network::count()) : ($user->network ? '1' : '0'),
            ],
            [
                'label' => 'Recent Entries',
                'value' => number_format((clone $collectionsScope)->where('created_at', '>=', Carbon::now()->subDays(7))->count()),
            ],
        ];

        $recentCollections = (clone $collectionsScope)
            ->latest()
            ->take(8)
            ->get([
                'username',
                'site_name',
                'bank',
                'insurance_transactions',
                'zwg_insurance_transactions',
                'created_at',
            ]);

        // Quick actions for ZINARA users
        if ($isZINARASupervisor) {
            $quickActions = [
                ['label' => 'Manage Face Values', 'description' => 'Receive stock and allocate to ZINARA clerks.', 'icon' => 'bi bi-stack', 'route' => route('supervisorfacevalues.index')],
                ['label' => 'Create ZINARA Clerk', 'description' => 'Add new ZINARA clerk to your team.', 'icon' => 'bi bi-person-plus', 'route' => route('teams.create')],
                ['label' => 'Stock Report', 'description' => 'View face value stock and allocations.', 'icon' => 'bi bi-box-seam', 'route' => route('facevalues.reports.stock')],
                ['label' => 'Daily Entries', 'description' => 'Review clerk face value declarations.', 'icon' => 'bi bi-calendar-day', 'route' => route('clientfvreport')],
            ];
        } elseif ($isZINARAClerk) {
            $quickActions = [
                ['label' => 'Face Value Declaration', 'description' => 'Declare usage against your assigned stock.', 'icon' => 'bi bi-upc-scan', 'route' => route('facevaluelist')],
                ['label' => 'Declaration History', 'description' => 'Review your face value declaration history.', 'icon' => 'bi bi-clock-history', 'route' => route('gethistory')],
                ['label' => 'Cumulative Report', 'description' => 'View your face value balance over time.', 'icon' => 'bi bi-calendar-range', 'route' => route('cumulativefvreport')],
                ['label' => 'Trace Face Value', 'description' => 'Track a specific face value number.', 'icon' => 'bi bi-search', 'route' => route('facevalues.reports.trace')],
            ];
        } else {
            $quickActions = match ($roleId) {
                2 => [
                    ['label' => 'New Collection', 'description' => 'Capture a fresh daily collection sheet.', 'icon' => 'bi bi-receipt-cutoff', 'route' => route('collection')],
                    ['label' => 'Face Value Declaration', 'description' => 'Declare usage against your assigned stock.', 'icon' => 'bi bi-upc-scan', 'route' => route('facevaluelist')],
                    ['label' => 'Collection History', 'description' => 'Review your submitted transactions.', 'icon' => 'bi bi-clock-history', 'route' => route('gettransactions')],
                    ['label' => 'Face Value History', 'description' => 'Inspect compiled face value movement.', 'icon' => 'bi bi-archive', 'route' => route('compiledhistory')],
                ],
                3 => [
                    ['label' => 'Manage Face Values', 'description' => 'Receive stock and allocate it to clerks.', 'icon' => 'bi bi-stack', 'route' => route('supervisorfacevalues.index')],
                    ['label' => 'Collection Oversight', 'description' => 'Review submitted collections across your scope.', 'icon' => 'bi bi-clipboard-data', 'route' => route('getmanagesheet')],
                    ['label' => 'Network Reports', 'description' => 'Open cumulative and network-level reporting.', 'icon' => 'bi bi-bar-chart-line', 'route' => route('collectionreports')],
                    ['label' => 'User Reports', 'description' => 'Inspect performance by individual user.', 'icon' => 'bi bi-person-lines-fill', 'route' => route('user.reports')],
                ],
                default => [
                    ['label' => 'Dashboard', 'description' => 'Review current performance and operational health.', 'icon' => 'bi bi-speedometer2', 'route' => route('home')],
                    ['label' => 'Manage Users', 'description' => 'Create and maintain team access.', 'icon' => 'bi bi-people', 'route' => route('teams.index')],
                    ['label' => 'Budgets', 'description' => 'Compare plan against actual collection figures.', 'icon' => 'bi bi-bank', 'route' => route('budgets.index')],
                    ['label' => 'Amendment Queue', 'description' => 'Review collection changes waiting approval.', 'icon' => 'bi bi-pencil-square', 'route' => route('dailycollection.ammendmentrequestlist')],
                ],
            };
        }

        return view('index', compact(
            'lineChartLabels',
            'lineChartUsdData',
            'lineChartZwgData',
            'barChartLabels',
            'barChartUsdData',
            'barChartZwgData',
            'networkReportLabels',
            'networkReportUsdData',
            'networkReportZwgData',
            'summaryCards',
            'dashboardSpotlights',
            'recentCollections',
            'quickActions',
            'pendingAmendments',
            // ZINARA-specific variables
            'isZINARAUser',
            'isZINARASupervisor',
            'isZINARAClerk',
            'zinaraLineChartLabels',
            'zinaraUsedData',
            'zinaraSpoiledData',
            'zinaraPieLabels',
            'zinaraPieData',
            'clerkBalanceLabels',
            'clerkBalanceData',
            'recentDeclarations',
            'lowStockAlerts'
        ));
    }
    public function collectionreports(Request $request)
    {
        $roleId = (int) Auth::user()->role_id;
        $network = $request->network;
        $site = $request->site;
        $startdate = $request->startdate ?: Carbon::now()->toDateString();
        $enddate = $request->enddate ?: Carbon::now()->toDateString();
        $resolvedSbu = $this->resolvedReportSbu($request);

        $transactions = DailyCollection::with(['site', 'network', 'user'])
            ->whereBetween('created_at', [
                Carbon::parse($startdate)->startOfDay(),
                Carbon::parse($enddate)->endOfDay(),
            ]);

        $this->applyCollectionReportScope($transactions, $resolvedSbu);

        if (!empty($network)) {
            $transactions->where('networkid', $network);
        }

        if (!empty($site)) {
            $transactions->where('siteid', $site);
        }

        $transactions = $transactions->get()
            ->groupBy(function ($transaction) {
                return $transaction->siteid ?: 'site_' . ($transaction->site_name ?: 'unassigned');
            })
            ->map(function ($siteTransactions) use ($startdate, $enddate) {
                $firstTransaction = $siteTransactions->first();

                $sumFields = [
                    'insurance_transactions',
                    'zinara_fees',
                    'third_party_premiums',
                    'full_cover_premiums',
                    'usd_total_deposited',
                    'usd_cash',
                    'usd_swipe',
                    'usd_transfers',
                    'usd_cash_in_hand',
                    'usd_cash_in_hand_balance',
                    'usd_debit_sales',
                    'usd_credit_sales',
                    'zwg_insurance_transactions',
                    'zwg_zinara_fees',
                    'zwg_third_party_premiums',
                    'zwg_full_cover_premiums',
                    'zwg_total_deposited',
                    'zwg_cash',
                    'zwg_swipe',
                    'zwg_transfers',
                    'zwg_cash_in_hand',
                    'zwg_cash_in_hand_balance',
                    'zwg_debit_sales',
                    'zwg_credit_sales',
                ];

                $aggregated = [
                    'siteid' => $firstTransaction->siteid,
                    'site_name' => $firstTransaction->site_name ?: optional($firstTransaction->site)->site_name ?: 'Unassigned Site',
                    'networkid' => $firstTransaction->networkid,
                    'network_name' => optional($firstTransaction->network)->name ?: 'Unassigned Network',
                    'report_period' => Carbon::parse($startdate)->format('d/m/Y') . ' - ' . Carbon::parse($enddate)->format('d/m/Y'),
                ];

                foreach ($sumFields as $field) {
                    $aggregated[$field] = (float) $siteTransactions->sum($field);
                }

                return (object) $aggregated;
            })
            ->sortBy('site_name')
            ->values();

        $networks = $this->getCollectionReportNetworks($roleId, $resolvedSbu);
        $sbuOptions = $this->getCollectionReportSbuOptions($roleId, $resolvedSbu);

        return view('Reports.index', [
            'networks' => $networks,
            'transactions' => $transactions,
            'sbuOptions' => $sbuOptions,
            'selectedSbu' => $resolvedSbu,
            'canFilterBySbu' => $this->canUseSbuReportFilter($roleId),
            'isSbuLocked' => $this->isReportSupervisor($roleId) && filled($resolvedSbu),
        ]);
    }

    public function bysitesreports(Request $request)
    {
        $roleId = (int) Auth::user()->role_id;
        $network= $request->network;
        $siteIds = $request->site;
        $startdate= $request->startdate ?: Carbon::now()->toDateString();
        $enddate= $request->enddate ?: Carbon::now()->toDateString();
        $resolvedSbu = $this->resolvedReportSbu($request);

        $query = DailyCollection::with(['site', 'network', 'user'])
            ->whereBetween('created_at', [
                Carbon::parse($startdate)->startOfDay(),
                Carbon::parse($enddate)->endOfDay(),
            ]);

        $this->applyCollectionReportScope($query, $resolvedSbu);

        if (!empty($network)) {
            $query->where('networkid', $network);
        }

        if (!empty($siteIds)) { // Check if site IDs are selected
            $query->whereIn('siteid', $siteIds);
        }

        $sql = $query->get();
        $networks = $this->getCollectionReportNetworks($roleId, $resolvedSbu);
        $allowedSiteIds = ($resolvedSbu && $this->canUseSbuReportFilter($roleId))
            ? $this->getSiteIdsForReportSbu($resolvedSbu)
            : null;

        $sites = site::query()
            ->when($allowedSiteIds !== null, fn ($siteQuery) => $siteQuery->whereIn('id', $allowedSiteIds->isNotEmpty() ? $allowedSiteIds : [-1]))
            ->orderBy('site_name')
            ->get();

        return view('Reports.bysite', [
            'sites' => $sites,
            'networks' => $networks,
            'transactions' => $sql,
            'sbuOptions' => $this->getCollectionReportSbuOptions($roleId, $resolvedSbu),
            'selectedSbu' => $resolvedSbu,
            'canFilterBySbu' => $this->canUseSbuReportFilter($roleId),
            'isSbuLocked' => $this->isReportSupervisor($roleId) && filled($resolvedSbu),
        ]);
    }



    public function getsites(Request $request, $networkId)
    {
        $resolvedSbu = $this->resolvedReportSbu($request);
        $roleId = (int) Auth::user()->role_id;
        $allowedSiteIds = ($resolvedSbu && $this->canUseSbuReportFilter($roleId))
            ? $this->getSiteIdsForReportSbu($resolvedSbu)
            : null;

        $sites = site::where('network_id', $networkId)
            ->when($allowedSiteIds !== null, fn ($query) => $query->whereIn('id', $allowedSiteIds->isNotEmpty() ? $allowedSiteIds : [-1]))
            ->orderBy('site_name')
            ->get();

        return response()->json($sites);
    }
    public function zcash()

    {

        $sql = DailyCollection::where('');
        return view('index');
    }













/**
     * Show the application dashboard with chart data.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function pamba()
    {
       
}



}

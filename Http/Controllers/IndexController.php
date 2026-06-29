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
    private const COURIER_SUPERVISOR_ROLE_ID = 6;
    private const COURIER_PLATFORM = 'Courier Connect';
    private function getCourierConnectCourierClerkIds(): array
    {
        return User::whereIn('role_id', [2, 7])
            ->whereHas('site', function ($query) {
                $query->whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)]);
            })
            ->pluck('id')
            ->toArray();
    }

    private function isCourierSupervisorRole(int $roleId): bool
    {
        return $roleId === self::COURIER_SUPERVISOR_ROLE_ID;
    }

    private function getCourierSupervisorSiteIds()
    {
        return User::whereIn('id', $this->getCourierConnectCourierClerkIds())
            ->pluck('siteid')
            ->filter()
            ->unique()
            ->values();
    }

    private function applyCourierSupervisorCollectionScope($query)
    {
        $clerkIds = $this->getCourierConnectCourierClerkIds();

        return $query->whereIn('user_id', !empty($clerkIds) ? $clerkIds : [-1]);
    }

    private function applyCourierConnectCollectionPlatformScope($query): void
    {
        $query->whereRaw('UPPER(TRIM(daily_collections.platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)]);
    }

    private function getCourierConnectCollectionSiteIds()
    {
        return DailyCollection::query()
            ->whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)])
            ->pluck('siteid')
            ->filter()
            ->unique()
            ->values();
    }

    private function getCourierConnectCollectionSites()
    {
        $siteIds = $this->getCourierConnectCollectionSiteIds();

        return site::query()
            ->whereIn('id', $siteIds->isNotEmpty() ? $siteIds : [-1])
            ->orderBy('site_name')
            ->get();
    }

    private function getCourierConnectCollectionNetworks()
    {
        $networkIds = DailyCollection::query()
            ->whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)])
            ->pluck('networkid')
            ->filter()
            ->unique()
            ->values();

        return Network::query()
            ->whereIn('id', $networkIds->isNotEmpty() ? $networkIds : [-1])
            ->orderBy('name')
            ->get();
    }

    private function getCourierSupervisorCollectionNetworks()
    {
        $siteIds = $this->getCourierSupervisorSiteIds();
        $networkIds = site::query()
            ->whereIn('id', $siteIds->isNotEmpty() ? $siteIds : [-1])
            ->pluck('network_id')
            ->filter()
            ->unique()
            ->values();

        return Network::query()
            ->whereIn('id', $networkIds->isNotEmpty() ? $networkIds : [-1])
            ->orderBy('name')
            ->get();
    }

    private function getCourierSupervisorReportSites()
    {
        $siteIds = $this->getCourierSupervisorSiteIds();

        return site::query()
            ->whereIn('id', $siteIds->isNotEmpty() ? $siteIds : [-1])
            ->orderBy('site_name')
            ->get();
    }

    private function getCourierConnectPlatformSites()
    {
        return site::query()
            ->whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)])
            ->orderBy('site_name')
            ->get();
    }

    private function getCourierConnectPlatformNetworks()
    {
        $networkIds = $this->getCourierConnectPlatformSites()
            ->pluck('network_id')
            ->filter()
            ->unique()
            ->values();

        return Network::query()
            ->whereIn('id', $networkIds->isNotEmpty() ? $networkIds : [-1])
            ->orderBy('name')
            ->get();
    }

    private function getCourierSupervisorRegionOptions()
    {
        return collect();
    }

    private function getReportUserRegion(): ?string
    {
        $user = Auth::user();

        if ($user?->network?->name) {
            return $user->network->name;
        }

        return null;
    }

    private function isReportSupervisor(int $roleId): bool
    {
        return in_array($roleId, [3, 6], true);
    }

    private function isRegularSupervisor(int $roleId): bool
    {
        return $roleId === 2;
    }

    private function hasGlobalReportAccess(int $roleId): bool
    {
        return in_array($roleId, [1, 5], true);
    }

    private function canUseRegionReportFilter(int $roleId): bool
    {
        return $this->isReportSupervisor($roleId)
            || $this->hasGlobalReportAccess($roleId)
            || $roleId === 8
            || $this->isRegularSupervisor($roleId);
    }

    /**
     * Determine the effective Region filter for reports.
     */
    private function resolvedReportRegion(Request $request): ?int
    {
        $roleId = (int) Auth::user()->role_id;

        if ($this->isCourierSupervisorRole($roleId)) {
            return Auth::user()->networkid;
        }

        if (in_array($roleId, [4, 8], true)) {
            return Auth::user()->networkid;
        }

        if ($this->hasGlobalReportAccess($roleId) || $roleId === 3 || $this->isRegularSupervisor($roleId)) {
            return $request->filled('region') ? (int) $request->input('region') : null;
        }

        return null;
    }

    private function resolvedReportSbu(Request $request): ?int
    {
        return $this->resolvedReportRegion($request);
    }

    private function applyCollectionReportScope($query, ?int $regionId = null)
    {
        $roleId = (int) Auth::user()->role_id;

        if ($regionId && $this->canUseRegionReportFilter($roleId)) {
            $siteIds = site::where('network_id', $regionId)->pluck('id');
            $query->whereIn('siteid', $siteIds->isNotEmpty() ? $siteIds : [-1]);
        }

        return $query;
    }

    private function canUseSbuReportFilter(int $roleId): bool
    {
        return $this->canUseRegionReportFilter($roleId);
    }

    private function getSiteIdsForReportSbu(?int $regionId)
    {
        if (!$regionId) {
            return collect();
        }

        return site::where('network_id', $regionId)->pluck('id')->values();
    }

    /**
     * Get the Region options to display in the report filter dropdown.
     */
    private function getCollectionReportRegionOptions(int $roleId, ?int $resolvedRegionId)
    {
        if (in_array($roleId, [4, 8], true) && $resolvedRegionId) {
            return Network::whereKey($resolvedRegionId)->orderBy('name')->get();
        }

        return Network::orderBy('name')->get();
    }

    private function getCollectionReportSbuOptions(int $roleId, ?int $resolvedRegionId)
    {
        return $this->getCollectionReportRegionOptions($roleId, $resolvedRegionId);
    }

    private function getCourierSupervisorSbuOptions()
    {
        return $this->getCourierSupervisorRegionOptions();
    }

    private function getCollectionReportNetworks(int $roleId, ?int $resolvedRegionId)
    {
        if ($resolvedRegionId && $this->canUseRegionReportFilter($roleId)) {
            return Network::whereKey($resolvedRegionId)->orderBy('name')->get();
        }

        return Network::orderBy('name')->get();
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
        $isZIMPOSTViewer = ($roleId == 8);
        $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
        $isElevated = in_array($roleId, [1, 3, 4, 5], true);
        $lastThirtyDays = Carbon::now()->subDays(30);
        $zimpostSiteIds = $isZIMPOSTViewer
            ? site::query()
                ->whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)])
                ->pluck('id')
            : collect();

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
        $authorizedCourierClerkIds = $isZINARASupervisor ? $this->getCourierConnectCourierClerkIds() : [];
        $authorizedCourierSiteIds = $isZINARASupervisor ? $this->getCourierSupervisorSiteIds() : collect();

        // ZINARA-specific data for roles 6 and 7
        if ($isZINARAUser) {
            // Get recent declarations
            if ($isZINARASupervisor) {
                $recentDeclarations = FaceValue::where('is_parent', false)
                    ->whereIn('clerk_id', $authorizedCourierClerkIds)
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
                
                $lowStockAlerts = FaceValue::where('is_parent', true)
                    ->whereIn('clerk_id', $authorizedCourierClerkIds)
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
                ->when($isZINARASupervisor, function($query) use ($authorizedCourierClerkIds) {
                    return $query->whereIn('clerk_id', $authorizedCourierClerkIds);
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
                $totalStock = FaceValue::where('is_parent', true)
                    ->whereIn('clerk_id', $authorizedCourierClerkIds)
                    ->sum('received');
                
                $totalUsedSpoiled = FaceValue::where('is_parent', false)
                    ->whereIn('clerk_id', $authorizedCourierClerkIds)
                    ->select(DB::raw('SUM(used) + SUM(spoiled) as total'))
                    ->value('total') ?? 0;
                
                $currentBalance = FaceValue::where('is_parent', true)
                    ->whereIn('clerk_id', $authorizedCourierClerkIds)
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
                $clerkBalances = FaceValue::where('is_parent', true)
                    ->whereIn('clerk_id', $authorizedCourierClerkIds)
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
        if ($isZINARASupervisor) {
            $collectionsScope->whereIn('siteid', $authorizedCourierSiteIds->isNotEmpty() ? $authorizedCourierSiteIds : [-1]);
        } elseif ($isZIMPOSTViewer) {
            $collectionsScope->whereIn('siteid', $zimpostSiteIds->isNotEmpty() ? $zimpostSiteIds : [-1]);
        } elseif (!$isElevated && !$isZINARAUser) {
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
                ->when($isZIMPOSTViewer, fn($query) => $query->whereIn('siteid', $zimpostSiteIds->isNotEmpty() ? $zimpostSiteIds : [-1]))
                ->when(!$isElevated && !$isZIMPOSTViewer, fn($query) => $query->where('user_id', $userId))
                ->where('created_at', '>=', $lastThirtyDays)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $dailyZwgTransactionsForChart = DB::table('daily_collections')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(zwg_insurance_transactions) as total_transactions'))
                ->when($isZIMPOSTViewer, fn($query) => $query->whereIn('siteid', $zimpostSiteIds->isNotEmpty() ? $zimpostSiteIds : [-1]))
                ->when(!$isElevated && !$isZIMPOSTViewer, fn($query) => $query->where('user_id', $userId))
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
                ->when($isZIMPOSTViewer, fn($query) => $query->whereIn('siteid', $zimpostSiteIds->isNotEmpty() ? $zimpostSiteIds : [-1]))
                ->when(!$isElevated && !$isZIMPOSTViewer, fn($query) => $query->where('user_id', $userId))
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
                ->when($isZIMPOSTViewer, fn($query) => $query->whereIn('daily_collections.siteid', $zimpostSiteIds->isNotEmpty() ? $zimpostSiteIds : [-1]))
                ->when(!$isElevated && !$isZIMPOSTViewer, fn($query) => $query->where('daily_collections.user_id', $userId))
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
                        ->when($isZINARASupervisor, function($query) use ($authorizedCourierClerkIds) {
                            return $query->whereIn('clerk_id', $authorizedCourierClerkIds);
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
                        ->when($isZINARASupervisor, function($query) use ($authorizedCourierClerkIds) {
                            return $query->whereIn('clerk_id', $authorizedCourierClerkIds);
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
                        ? number_format(count($authorizedCourierClerkIds))
                        : number_format(FaceValue::where('clerk_id', $userId)->where('is_parent', false)->count()),
                    'note' => $isZINARASupervisor ? 'ZINARA clerks under your supervision.' : 'Total declarations submitted.',
                    'icon' => $isZINARASupervisor ? 'bi bi-people' : 'bi bi-journal-text',
                ],
            ];
        } else {
            if ($isZIMPOSTViewer) {
                $summaryCards = [
                    [
                        'label' => 'Courier USD Collections',
                        'value' => '$' . number_format($collectionWindow->sum('insurance_transactions'), 2),
                        'note' => 'Courier Connect USD activity over the last 30 days.',
                        'icon' => 'bi bi-cash-coin',
                    ],
                    [
                        'label' => 'Courier ZWG Collections',
                        'value' => 'ZWG ' . number_format($collectionWindow->sum('zwg_insurance_transactions'), 2),
                        'note' => 'Courier Connect ZWG activity over the last 30 days.',
                        'icon' => 'bi bi-wallet2',
                    ],
                    [
                        'label' => 'Courier Submissions Today',
                        'value' => number_format((clone $collectionsScope)->whereDate('created_at', Carbon::today())->count()),
                        'note' => 'Read-only count of Courier site entries submitted today.',
                        'icon' => 'bi bi-journal-check',
                    ],
                    [
                        'label' => 'Courier Sites In View',
                        'value' => number_format($zimpostSiteIds->count()),
                        'note' => 'Courier Connect sites available to this viewer dashboard.',
                        'icon' => 'bi bi-building',
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
        }

        $dashboardSpotlights = [
            [
                'label' => $isZIMPOSTViewer ? 'Courier Active Sites' : 'Active Sites',
                'value' => number_format((clone $collectionsScope)->where('created_at', '>=', $lastThirtyDays)->distinct('siteid')->count('siteid')),
            ],
            [
                'label' => 'Networks In View',
                'value' => $isZIMPOSTViewer
                    ? number_format((clone $collectionsScope)->where('created_at', '>=', $lastThirtyDays)->distinct('networkid')->count('networkid'))
                    : ($isElevated ? number_format(Network::count()) : ($user->network ? '1' : '0')),
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
                ['label' => 'Manage Face Values', 'description' => 'Receive stock and allocate to Courier clerks.', 'icon' => 'bi bi-stack', 'route' => route('supervisorfacevalues.index')],
                ['label' => 'Create Courier Clerk', 'description' => 'Add a clerk assigned to a Courier Connect site.', 'icon' => 'bi bi-person-plus', 'route' => route('teams.create')],
                ['label' => 'Reports Hub', 'description' => 'Review Courier Connect collection reports and operational metrics.', 'icon' => 'bi bi-grid-1x2', 'route' => route('reports.hub')],
                ['label' => 'Courier Site Reports', 'description' => 'Review Courier Connect site collection totals.', 'icon' => 'bi bi-building', 'route' => route('reports.courier-sites')],
            ];
        } elseif ($isZINARAClerk) {
            $quickActions = [
                ['label' => 'Face Value Declaration', 'description' => 'Declare usage against your assigned stock.', 'icon' => 'bi bi-upc-scan', 'route' => route('facevaluelist')],
                ['label' => 'Declaration History', 'description' => 'Review your face value declaration history.', 'icon' => 'bi bi-clock-history', 'route' => route('gethistory')],
                ['label' => 'Cumulative Report', 'description' => 'View your face value balance over time.', 'icon' => 'bi bi-calendar-range', 'route' => route('cumulativefvreport')],
                ['label' => 'Trace Face Value', 'description' => 'Track a specific face value number.', 'icon' => 'bi bi-search', 'route' => route('facevalues.reports.trace')],
            ];
        } elseif ($isZIMPOSTViewer) {
            $quickActions = [
                ['label' => 'Courier Sites', 'description' => 'View Courier Connect sites available to ZIMPOST reporting.', 'icon' => 'bi bi-building', 'route' => route('sites')],
                ['label' => 'Courier Site Reports', 'description' => 'Review Courier site collection totals.', 'icon' => 'bi bi-graph-up', 'route' => route('reports.courier-sites')],
                ['label' => 'Courier Network Reports', 'description' => 'Open network-level Courier collection reports.', 'icon' => 'bi bi-bar-chart-line', 'route' => route('collectionreports')],
                ['label' => 'Reports Hub', 'description' => 'Use the read-only reporting workspace.', 'icon' => 'bi bi-grid-1x2', 'route' => route('reports.hub')],
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
            'isZIMPOSTViewer',
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

        if ($this->isCourierSupervisorRole($roleId)) {
            return $this->courierSupervisorCollectionReports($request);
        }

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

        $transactions = $this->buildCollectionReportRows($transactions->get(), $startdate, $enddate);

        $networks = $this->getCollectionReportNetworks($roleId, $resolvedSbu);
        $sbuOptions = $this->getCollectionReportSbuOptions($roleId, $resolvedSbu);

        // SBU dropdown is never locked for regular supervisors (role 3)
        $isSbuLocked = ($this->isReportSupervisor($roleId) && $roleId !== 3 && filled($resolvedSbu));

        return view('Reports.index', [
            'networks' => $networks,
            'transactions' => $transactions,
            'sbuOptions' => $sbuOptions,
            'selectedSbu' => $resolvedSbu,
            'canFilterBySbu' => $this->canUseSbuReportFilter($roleId),
            'isSbuLocked' => $isSbuLocked,
        ]);
    }

    private function courierSupervisorCollectionReports(Request $request)
    {
        $network = $request->network;
        $site = $request->site;
        $startdate = $request->startdate ?: Carbon::now()->toDateString();
        $enddate = $request->enddate ?: Carbon::now()->toDateString();

        $transactions = DailyCollection::with(['site', 'network', 'user'])
            ->whereBetween('created_at', [
                Carbon::parse($startdate)->startOfDay(),
                Carbon::parse($enddate)->endOfDay(),
            ]);

        $this->applyCourierSupervisorCollectionScope($transactions);

        if (!empty($network)) {
            $transactions->where('networkid', $network);
        }

        if (!empty($site)) {
            $transactions->where('siteid', $site);
        }

        return view('Reports.index', [
            'networks' => $this->getCourierSupervisorCollectionNetworks(),
            'transactions' => $this->buildCollectionReportRows($transactions->get(), $startdate, $enddate),
            'sbuOptions' => $this->getCourierSupervisorSbuOptions(),
            'selectedSbu' => null,
            'canFilterBySbu' => false,
            'isSbuLocked' => false,
        ]);
    }

    private function buildCollectionReportRows($transactions, string $startdate, string $enddate)
    {
        return $transactions
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
    }

    public function bysitesreports(Request $request)
    {
        $roleId = (int) Auth::user()->role_id;

        if ($this->isCourierSupervisorRole($roleId)) {
            return $this->courierSupervisorBySitesReports($request);
        }

        $network = $request->network;
        $siteIds = $request->site;
        $startdate = $request->startdate ?: Carbon::now()->toDateString();
        $enddate = $request->enddate ?: Carbon::now()->toDateString();
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

        if (!empty($siteIds)) {
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

        $sbuOptions = $this->getCollectionReportSbuOptions($roleId, $resolvedSbu);

        // SBU dropdown is never locked for regular supervisors (role 3)
        $isSbuLocked = ($this->isReportSupervisor($roleId) && $roleId !== 3 && filled($resolvedSbu));

        return view('Reports.bysite', [
            'sites' => $sites,
            'networks' => $networks,
            'transactions' => $sql,
            'sbuOptions' => $sbuOptions,
            'selectedSbu' => $resolvedSbu,
            'canFilterBySbu' => $this->canUseSbuReportFilter($roleId),
            'isSbuLocked' => $isSbuLocked,
        ]);
    }

    private function courierSupervisorBySitesReports(Request $request)
    {
        $network = $request->network;
        $siteIds = $request->site;
        $startdate = $request->startdate ?: Carbon::now()->toDateString();
        $enddate = $request->enddate ?: Carbon::now()->toDateString();

        $query = DailyCollection::with(['site', 'network', 'user'])
            ->whereBetween('created_at', [
                Carbon::parse($startdate)->startOfDay(),
                Carbon::parse($enddate)->endOfDay(),
            ]);

        $this->applyCourierSupervisorCollectionScope($query);

        if (!empty($network)) {
            $query->where('networkid', $network);
        }

        if (!empty($siteIds)) {
            $query->whereIn('siteid', $siteIds);
        }

        return view('Reports.bysite', [
            'sites' => $this->getCourierSupervisorReportSites(),
            'networks' => $this->getCourierSupervisorCollectionNetworks(),
            'transactions' => $query->get(),
            'sbuOptions' => $this->getCourierSupervisorSbuOptions(),
            'selectedSbu' => null,
            'canFilterBySbu' => false,
            'isSbuLocked' => false,
        ]);
    }

    public function onlySalesPeriodReport(Request $request)
    {
        return $this->onlySalesReport($request, 'period');
    }

    public function onlySalesDailyReport(Request $request)
    {
        return $this->onlySalesReport($request, 'daily');
    }

    public function onlySalesDailySupervisorReport(Request $request)
    {
        return $this->supervisorUnfilteredDailyOnlySalesReport($request);
    }

    public function onlyCashPeriodReport(Request $request)
    {
        return $this->onlyCashReport($request, 'period');
    }

    public function onlyCashDailyReport(Request $request)
    {
        return $this->onlyCashReport($request, 'daily');
    }

    public function onlyCashDailySupervisorReport(Request $request)
    {
        return $this->supervisorUnfilteredDailyOnlyCashReport($request);
    }

    private function onlySalesReport(Request $request, string $mode)
    {
        $roleId = (int) Auth::user()->role_id;

        if (!in_array($roleId, [3, 6], true)) {
            abort(403, 'Only supervisors and courier supervisors can access this report.');
        }

        if ($this->isCourierSupervisorRole($roleId)) {
            return $mode === 'daily'
                ? $this->courierSupervisorDailyOnlySalesReport($request)
                : $this->courierSupervisorPeriodOnlySalesReport($request);
        }

        $resolvedSbu = $this->resolvedReportSbu($request);
        $isDaily = $mode === 'daily';
        $selectedDate = $request->input('report_date') ?: Carbon::now()->toDateString();
        $startdate = $isDaily ? $selectedDate : ($request->input('startdate') ?: Carbon::now()->toDateString());
        $enddate = $isDaily ? $selectedDate : ($request->input('enddate') ?: Carbon::now()->toDateString());

        $start = Carbon::parse($startdate)->startOfDay();
        $end = Carbon::parse($enddate)->endOfDay();
        $network = $request->input('network');
        $siteIds = array_filter((array) $request->input('site', []));

        $query = DailyCollection::with(['site', 'network', 'user']);
        $this->applyOnlySalesDateFilter($query, $start, $end);
        $this->applyCollectionReportScope($query, $resolvedSbu);

        if (!empty($network)) {
            $query->where('networkid', $network);
        }

        if (!empty($siteIds)) {
            $query->whereIn('siteid', $siteIds);
        }

        $allowedSiteIds = ($resolvedSbu && $this->canUseSbuReportFilter($roleId))
            ? $this->getSiteIdsForReportSbu($resolvedSbu)
            : null;

        $reportSitesQuery = site::query()
            ->when($allowedSiteIds !== null, fn ($siteQuery) => $siteQuery->whereIn('id', $allowedSiteIds->isNotEmpty() ? $allowedSiteIds : [-1]))
            ->when(!empty($network), fn ($siteQuery) => $siteQuery->where('network_id', $network))
            ->when(!empty($siteIds), fn ($siteQuery) => $siteQuery->whereIn('id', $siteIds));

        $reportSites = $reportSitesQuery->orderBy('site_name')->get();
        $transactions = $query->get();
        $usdRows = $isDaily
            ? $this->buildDailyOnlySalesRows($reportSites, $transactions, 'USD', $start)
            : $this->buildOnlySalesRows($transactions, 'USD', $start, $end, false);
        $zwgRows = $isDaily
            ? $this->buildDailyOnlySalesRows($reportSites, $transactions, 'ZWG', $start)
            : $this->buildOnlySalesRows($transactions, 'ZWG', $start, $end, false);

        $networks = $this->getCollectionReportNetworks($roleId, $resolvedSbu);

        $sites = site::query()
            ->when($allowedSiteIds !== null, fn ($siteQuery) => $siteQuery->whereIn('id', $allowedSiteIds->isNotEmpty() ? $allowedSiteIds : [-1]))
            ->orderBy('site_name')
            ->get();

        $sbuOptions = $this->getCollectionReportSbuOptions($roleId, $resolvedSbu);
        $isSbuLocked = ($this->isReportSupervisor($roleId) && $roleId !== 3 && filled($resolvedSbu));

        return view('Reports.only-sales', [
            'mode' => $mode,
            'isDaily' => $isDaily,
            'usdRows' => $usdRows,
            'zwgRows' => $zwgRows,
            'networks' => $networks,
            'sites' => $sites,
            'sbuOptions' => $sbuOptions,
            'selectedSbu' => $resolvedSbu,
            'canFilterBySbu' => $this->canUseSbuReportFilter($roleId),
            'isSbuLocked' => $isSbuLocked,
            'startdate' => $start->toDateString(),
            'enddate' => $end->toDateString(),
            'selectedDate' => Carbon::parse($selectedDate)->toDateString(),
            'selectedSites' => array_values(array_map('strval', $siteIds)),
            'selectedNetwork' => $network,
        ]);
    }

    private function courierSupervisorPeriodOnlySalesReport(Request $request)
    {
        $startdate = $request->input('startdate') ?: Carbon::now()->toDateString();
        $enddate = $request->input('enddate') ?: Carbon::now()->toDateString();

        $start = Carbon::parse($startdate)->startOfDay();
        $end = Carbon::parse($enddate)->endOfDay();
        $network = $request->input('network');
        $siteIds = array_filter((array) $request->input('site', []));

        $query = DailyCollection::with(['site', 'network', 'user']);
        $this->applyOnlySalesDateFilter($query, $start, $end);
        $this->applyCourierSupervisorCollectionScope($query);

        if (!empty($network)) {
            $query->where('networkid', $network);
        }

        if (!empty($siteIds)) {
            $query->whereIn('siteid', $siteIds);
        }

        $transactions = $query->get();
        $usdRows = $this->buildOnlySalesRows($transactions, 'USD', $start, $end, false);
        $zwgRows = $this->buildOnlySalesRows($transactions, 'ZWG', $start, $end, false);

        return view('Reports.only-sales', [
            'mode' => 'period',
            'isDaily' => false,
            'usdRows' => $usdRows,
            'zwgRows' => $zwgRows,
            'networks' => $this->getCourierSupervisorCollectionNetworks(),
            'sites' => $this->getCourierSupervisorReportSites(),
            'sbuOptions' => $this->getCourierSupervisorSbuOptions(),
            'selectedSbu' => null,
            'canFilterBySbu' => false,
            'isSbuLocked' => false,
            'startdate' => $start->toDateString(),
            'enddate' => $end->toDateString(),
            'selectedDate' => $start->toDateString(),
            'selectedSites' => array_values(array_map('strval', $siteIds)),
            'selectedNetwork' => $network,
        ]);
    }

    private function courierSupervisorDailyOnlySalesReport(Request $request)
    {
        $selectedDate = $request->input('report_date') ?: Carbon::now()->toDateString();
        $start = Carbon::parse($selectedDate)->startOfDay();
        $end = Carbon::parse($selectedDate)->endOfDay();
        $network = $request->input('network');
        $siteIds = array_filter((array) $request->input('site', []));

        $query = DailyCollection::with(['site', 'network', 'user']);
        $this->applyDailyCollectionDateFilter($query, $start);
        $this->applyCourierConnectCollectionPlatformScope($query);

        if (!empty($network)) {
            $query->where('networkid', $network);
        }

        if (!empty($siteIds)) {
            $query->whereIn('siteid', $siteIds);
        }

        $transactions = $query
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        return view('Reports.only-sales', [
            'mode' => 'daily',
            'isDaily' => true,
            'usdRows' => $this->buildPostedOnlySalesRows($transactions, 'USD'),
            'zwgRows' => $this->buildPostedOnlySalesRows($transactions, 'ZWG'),
            'networks' => $this->getCourierConnectCollectionNetworks(),
            'sites' => $this->getCourierConnectCollectionSites(),
            'sbuOptions' => $this->getCourierSupervisorSbuOptions(),
            'selectedSbu' => null,
            'canFilterBySbu' => false,
            'isSbuLocked' => false,
            'startdate' => $start->toDateString(),
            'enddate' => $end->toDateString(),
            'selectedDate' => $start->toDateString(),
            'selectedSites' => array_values(array_map('strval', $siteIds)),
            'selectedNetwork' => $network,
        ]);
    }

    private function supervisorUnfilteredDailyOnlySalesReport(Request $request)
    {
        abort_unless(in_array((int) Auth::user()->role_id, [3, 5], true), 403, 'Only supervisors and super users can access this report.');

        $selectedDate = $request->input('report_date') ?: Carbon::now()->toDateString();
        $start = Carbon::parse($selectedDate)->startOfDay();
        $network = $request->input('network');
        $siteIds = array_filter((array) $request->input('site', []));

        $query = DailyCollection::with(['site', 'network', 'user']);
        $this->applyDailyCollectionDateFilter($query, $start);

        if (!empty($network)) {
            $query->where('networkid', $network);
        }

        if (!empty($siteIds)) {
            $query->whereIn('siteid', $siteIds);
        }

        $transactions = $query
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        return view('Reports.only-sales', [
            'mode' => 'daily-supervisor',
            'isDaily' => true,
            'usdRows' => $this->buildPostedOnlySalesRows($transactions, 'USD'),
            'zwgRows' => $this->buildPostedOnlySalesRows($transactions, 'ZWG'),
            'networks' => Network::query()->orderBy('name')->get(),
            'sites' => site::query()->orderBy('site_name')->get(),
            'sbuOptions' => collect(),
            'selectedSbu' => null,
            'canFilterBySbu' => false,
            'isSbuLocked' => false,
            'startdate' => $start->toDateString(),
            'enddate' => $start->toDateString(),
            'selectedDate' => $start->toDateString(),
            'selectedSites' => array_values(array_map('strval', $siteIds)),
            'selectedNetwork' => $network,
        ]);
    }

    private function onlyCashReport(Request $request, string $mode)
    {
        $roleId = (int) Auth::user()->role_id;

        if (!in_array($roleId, [3, 6], true)) {
            abort(403, 'Only supervisors and courier supervisors can access this report.');
        }

        if ($this->isCourierSupervisorRole($roleId)) {
            return $mode === 'daily'
                ? $this->courierSupervisorDailyOnlyCashReport($request)
                : $this->courierSupervisorPeriodOnlyCashReport($request);
        }

        $resolvedSbu = $this->resolvedReportSbu($request);
        $isDaily = $mode === 'daily';
        $selectedDate = $request->input('report_date') ?: Carbon::now()->toDateString();
        $startdate = $isDaily ? $selectedDate : ($request->input('startdate') ?: Carbon::now()->toDateString());
        $enddate = $isDaily ? $selectedDate : ($request->input('enddate') ?: Carbon::now()->toDateString());

        $start = Carbon::parse($startdate)->startOfDay();
        $end = Carbon::parse($enddate)->endOfDay();
        $network = $request->input('network');
        $siteIds = array_filter((array) $request->input('site', []));

        $query = DailyCollection::with(['site', 'network', 'user']);
        $this->applyOnlySalesDateFilter($query, $start, $end);
        $this->applyCollectionReportScope($query, $resolvedSbu);

        if (!empty($network)) {
            $query->where('networkid', $network);
        }

        if (!empty($siteIds)) {
            $query->whereIn('siteid', $siteIds);
        }

        $allowedSiteIds = ($resolvedSbu && $this->canUseSbuReportFilter($roleId))
            ? $this->getSiteIdsForReportSbu($resolvedSbu)
            : null;

        $transactions = $query
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        $usdRows = $this->buildOnlyCashRows($transactions, 'USD');
        $zwgRows = $this->buildOnlyCashRows($transactions, 'ZWG');
        $networks = $this->getCollectionReportNetworks($roleId, $resolvedSbu);

        $sites = site::query()
            ->when($allowedSiteIds !== null, fn ($siteQuery) => $siteQuery->whereIn('id', $allowedSiteIds->isNotEmpty() ? $allowedSiteIds : [-1]))
            ->orderBy('site_name')
            ->get();

        return view('Reports.only-cash', [
            'mode' => $mode,
            'isDaily' => $isDaily,
            'usdRows' => $usdRows,
            'zwgRows' => $zwgRows,
            'networks' => $networks,
            'sites' => $sites,
            'sbuOptions' => $this->getCollectionReportSbuOptions($roleId, $resolvedSbu),
            'selectedSbu' => $resolvedSbu,
            'canFilterBySbu' => $this->canUseSbuReportFilter($roleId),
            'isSbuLocked' => ($this->isReportSupervisor($roleId) && $roleId !== 3 && filled($resolvedSbu)),
            'startdate' => $start->toDateString(),
            'enddate' => $end->toDateString(),
            'selectedDate' => Carbon::parse($selectedDate)->toDateString(),
            'selectedSites' => array_values(array_map('strval', $siteIds)),
            'selectedNetwork' => $network,
        ]);
    }

    private function courierSupervisorPeriodOnlyCashReport(Request $request)
    {
        $startdate = $request->input('startdate') ?: Carbon::now()->toDateString();
        $enddate = $request->input('enddate') ?: Carbon::now()->toDateString();

        $start = Carbon::parse($startdate)->startOfDay();
        $end = Carbon::parse($enddate)->endOfDay();
        $network = $request->input('network');
        $siteIds = array_filter((array) $request->input('site', []));

        $query = DailyCollection::with(['site', 'network', 'user']);
        $this->applyOnlySalesDateFilter($query, $start, $end);
        $this->applyCourierSupervisorCollectionScope($query);

        if (!empty($network)) {
            $query->where('networkid', $network);
        }

        if (!empty($siteIds)) {
            $query->whereIn('siteid', $siteIds);
        }

        $transactions = $query
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        return view('Reports.only-cash', [
            'mode' => 'period',
            'isDaily' => false,
            'usdRows' => $this->buildOnlyCashRows($transactions, 'USD'),
            'zwgRows' => $this->buildOnlyCashRows($transactions, 'ZWG'),
            'networks' => $this->getCourierSupervisorCollectionNetworks(),
            'sites' => $this->getCourierSupervisorReportSites(),
            'sbuOptions' => $this->getCourierSupervisorSbuOptions(),
            'selectedSbu' => null,
            'canFilterBySbu' => false,
            'isSbuLocked' => false,
            'startdate' => $start->toDateString(),
            'enddate' => $end->toDateString(),
            'selectedDate' => $start->toDateString(),
            'selectedSites' => array_values(array_map('strval', $siteIds)),
            'selectedNetwork' => $network,
        ]);
    }

    private function courierSupervisorDailyOnlyCashReport(Request $request)
    {
        $selectedDate = $request->input('report_date') ?: Carbon::now()->toDateString();
        $start = Carbon::parse($selectedDate)->startOfDay();
        $end = Carbon::parse($selectedDate)->endOfDay();
        $network = $request->input('network');
        $siteIds = array_filter((array) $request->input('site', []));

        $query = DailyCollection::with(['site', 'network', 'user']);
        $this->applyDailyCollectionDateFilter($query, $start);
        $this->applyCourierConnectCollectionPlatformScope($query);

        if (!empty($network)) {
            $query->where('networkid', $network);
        }

        if (!empty($siteIds)) {
            $query->whereIn('siteid', $siteIds);
        }

        $transactions = $query
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        return view('Reports.only-cash', [
            'mode' => 'daily',
            'isDaily' => true,
            'usdRows' => $this->buildOnlyCashRows($transactions, 'USD'),
            'zwgRows' => $this->buildOnlyCashRows($transactions, 'ZWG'),
            'networks' => $this->getCourierConnectCollectionNetworks(),
            'sites' => $this->getCourierConnectCollectionSites(),
            'sbuOptions' => $this->getCourierSupervisorSbuOptions(),
            'selectedSbu' => null,
            'canFilterBySbu' => false,
            'isSbuLocked' => false,
            'startdate' => $start->toDateString(),
            'enddate' => $end->toDateString(),
            'selectedDate' => $start->toDateString(),
            'selectedSites' => array_values(array_map('strval', $siteIds)),
            'selectedNetwork' => $network,
        ]);
    }

    private function supervisorUnfilteredDailyOnlyCashReport(Request $request)
    {
        abort_unless(in_array((int) Auth::user()->role_id, [3, 5], true), 403, 'Only supervisors and super users can access this report.');

        $selectedDate = $request->input('report_date') ?: Carbon::now()->toDateString();
        $start = Carbon::parse($selectedDate)->startOfDay();
        $network = $request->input('network');
        $siteIds = array_filter((array) $request->input('site', []));

        $query = DailyCollection::with(['site', 'network', 'user']);
        $this->applyDailyCollectionDateFilter($query, $start);

        if (!empty($network)) {
            $query->where('networkid', $network);
        }

        if (!empty($siteIds)) {
            $query->whereIn('siteid', $siteIds);
        }

        $transactions = $query
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        return view('Reports.only-cash', [
            'mode' => 'daily-supervisor',
            'isDaily' => true,
            'usdRows' => $this->buildOnlyCashRows($transactions, 'USD'),
            'zwgRows' => $this->buildOnlyCashRows($transactions, 'ZWG'),
            'networks' => Network::query()->orderBy('name')->get(),
            'sites' => site::query()->orderBy('site_name')->get(),
            'sbuOptions' => collect(),
            'selectedSbu' => null,
            'canFilterBySbu' => false,
            'isSbuLocked' => false,
            'startdate' => $start->toDateString(),
            'enddate' => $start->toDateString(),
            'selectedDate' => $start->toDateString(),
            'selectedSites' => array_values(array_map('strval', $siteIds)),
            'selectedNetwork' => $network,
        ]);
    }

    private function applyOnlySalesDateFilter($query, Carbon $start, Carbon $end): void
    {
        $query->where(function ($dateQuery) use ($start, $end) {
            $dateQuery->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(function ($fallbackQuery) use ($start, $end) {
                    $fallbackQuery->whereNull('transaction_date')
                        ->whereBetween('created_at', [$start, $end]);
                });
        });
    }

    private function applyDailyCollectionDateFilter($query, Carbon $date): void
    {
        $query->where(function ($dateQuery) use ($date) {
            $dateQuery->whereDate('transaction_date', $date->toDateString())
                ->orWhereDate('created_at', $date->toDateString());
        });
    }

    private function buildOnlySalesRows($transactions, string $currency, Carbon $start, Carbon $end, bool $isDaily)
    {
        $fields = $currency === 'USD'
            ? [
                'zinara' => 'zinara_fees',
                'third_party' => 'third_party_premiums',
                'full_cover' => 'full_cover_premiums',
                'other' => 'other_insurances_usd',
            ]
            : [
                'zinara' => 'zwg_zinara_fees',
                'third_party' => 'zwg_third_party_premiums',
                'full_cover' => 'zwg_full_cover_premiums',
                'other' => 'other_insurances_zwg',
            ];

        $dateLabel = $isDaily
            ? $start->format('d/m/Y')
            : $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');

        return $transactions
            ->groupBy(fn ($transaction) => $transaction->siteid ?: 'site_' . ($transaction->site_name ?: 'unassigned'))
            ->map(function ($siteTransactions) use ($fields, $dateLabel) {
                $first = $siteTransactions->first();
                $zinaraFees = (float) $siteTransactions->sum($fields['zinara']);
                $thirdPartyPremiums = (float) $siteTransactions->sum($fields['third_party']);
                $fullCoverPremiums = (float) $siteTransactions->sum($fields['full_cover']);
                $otherInsurance = (float) $siteTransactions->sum($fields['other']);
                $insuranceTransactions = $thirdPartyPremiums + $fullCoverPremiums + $otherInsurance;
                $totalTransactions = $zinaraFees + $insuranceTransactions;

                return (object) [
                    'site_name' => $first->site_name ?: optional($first->site)->site_name ?: 'Unassigned Site',
                    'platform_name' => $first->platform_name ?: optional($first->site)->platform_name,
                    'sbu' => optional($first->site)->sbu,
                    'username' => $siteTransactions->pluck('username')->filter()->unique()->join(', '),
                    'date_label' => $dateLabel,
                    'zinara_fees' => $zinaraFees,
                    'third_party_premiums' => $thirdPartyPremiums,
                    'full_cover_premiums' => $fullCoverPremiums,
                    'other_insurance' => $otherInsurance,
                    'insurance_transactions' => $insuranceTransactions,
                    'total_transactions' => $totalTransactions,
                ];
            })
            ->filter(fn ($row) => $row->total_transactions > 0)
            ->sortBy('site_name')
            ->values();
    }

    private function buildDailyOnlySalesRows($sites, $transactions, string $currency, Carbon $date)
    {
        $fields = $currency === 'USD'
            ? [
                'zinara' => 'zinara_fees',
                'third_party' => 'third_party_premiums',
                'full_cover' => 'full_cover_premiums',
                'other' => 'other_insurances_usd',
            ]
            : [
                'zinara' => 'zwg_zinara_fees',
                'third_party' => 'zwg_third_party_premiums',
                'full_cover' => 'zwg_full_cover_premiums',
                'other' => 'other_insurances_zwg',
            ];

        $transactionsBySite = $transactions->groupBy(fn ($transaction) => (string) $transaction->siteid);
        $dateLabel = $date->format('d/m/Y');

        return $sites
            ->map(function ($site) use ($transactionsBySite, $fields, $dateLabel) {
                $siteTransactions = $transactionsBySite->get((string) $site->id, collect());
                $zinaraFees = (float) $siteTransactions->sum($fields['zinara']);
                $thirdPartyPremiums = (float) $siteTransactions->sum($fields['third_party']);
                $fullCoverPremiums = (float) $siteTransactions->sum($fields['full_cover']);
                $otherInsurance = (float) $siteTransactions->sum($fields['other']);
                $insuranceTransactions = $thirdPartyPremiums + $fullCoverPremiums + $otherInsurance;
                $totalTransactions = $zinaraFees + $insuranceTransactions;

                return (object) [
                    'site_name' => $site->site_name,
                    'platform_name' => $site->platform_name,
                    'sbu' => $site->sbu,
                    'username' => $siteTransactions->pluck('username')->filter()->unique()->join(', '),
                    'date_label' => $dateLabel,
                    'zinara_fees' => $zinaraFees,
                    'third_party_premiums' => $thirdPartyPremiums,
                    'full_cover_premiums' => $fullCoverPremiums,
                    'other_insurance' => $otherInsurance,
                    'insurance_transactions' => $insuranceTransactions,
                    'total_transactions' => $totalTransactions,
                ];
            })
            ->values();
    }

    private function buildPostedOnlySalesRows($transactions, string $currency)
    {
        $fields = $currency === 'USD'
            ? [
                'zinara' => 'zinara_fees',
                'third_party' => 'third_party_premiums',
                'full_cover' => 'full_cover_premiums',
                'other' => 'other_insurances_usd',
            ]
            : [
                'zinara' => 'zwg_zinara_fees',
                'third_party' => 'zwg_third_party_premiums',
                'full_cover' => 'zwg_full_cover_premiums',
                'other' => 'other_insurances_zwg',
            ];

        return $transactions
            ->map(function ($transaction) use ($fields) {
                $zinaraFees = (float) $transaction->{$fields['zinara']};
                $thirdPartyPremiums = (float) $transaction->{$fields['third_party']};
                $fullCoverPremiums = (float) $transaction->{$fields['full_cover']};
                $otherInsurance = (float) $transaction->{$fields['other']};
                $insuranceTransactions = $thirdPartyPremiums + $fullCoverPremiums + $otherInsurance;
                $totalTransactions = $zinaraFees + $insuranceTransactions;
                $date = $transaction->transaction_date ?: $transaction->created_at;

                return (object) [
                    'site_name' => $transaction->site_name ?: optional($transaction->site)->site_name ?: 'Unassigned Site',
                    'platform_name' => $transaction->platform_name ?: optional($transaction->site)->platform_name,
                    'sbu' => optional($transaction->site)->sbu,
                    'username' => $transaction->username ?: optional($transaction->user)->name,
                    'date_label' => $date ? Carbon::parse($date)->format('d/m/Y') : '',
                    'zinara_fees' => $zinaraFees,
                    'third_party_premiums' => $thirdPartyPremiums,
                    'full_cover_premiums' => $fullCoverPremiums,
                    'other_insurance' => $otherInsurance,
                    'insurance_transactions' => $insuranceTransactions,
                    'total_transactions' => $totalTransactions,
                ];
            })
            ->filter(fn ($row) => $row->total_transactions > 0)
            ->sortBy(fn ($row) => $row->site_name . '|' . $row->username)
            ->values();
    }

    private function buildOnlyCashRows($transactions, string $currency)
    {
        $fields = $currency === 'USD'
            ? [
                'cash' => 'usd_cash',
                'swipe' => 'usd_swipe',
                'transfers' => 'usd_transfers',
                'mpos' => 'usd_mpos',
                'cash_balance' => 'usd_cash_in_hand_balance',
                'cash_deposited' => 'usd_total_deposited',
            ]
            : [
                'cash' => 'zwg_cash',
                'swipe' => 'zwg_swipe',
                'transfers' => 'zwg_transfers',
                'mpos' => 'zwg_mpos',
                'cash_balance' => 'zwg_cash_in_hand_balance',
                'cash_deposited' => 'zwg_total_deposited',
            ];

        return $transactions
            ->map(function ($transaction) use ($fields) {
                $cash = (float) $transaction->{$fields['cash']};
                $swipe = (float) $transaction->{$fields['swipe']};
                $transfers = (float) $transaction->{$fields['transfers']};
                $mpos = (float) $transaction->{$fields['mpos']};
                $date = $transaction->transaction_date ?: $transaction->created_at;

                return (object) [
                    'site_name' => $transaction->site_name ?: optional($transaction->site)->site_name ?: 'Unassigned Site',
                    'platform_name' => $transaction->platform_name ?: optional($transaction->site)->platform_name,
                    'sbu' => optional($transaction->site)->sbu,
                    'username' => $transaction->username ?: optional($transaction->user)->name,
                    'date_label' => $date ? Carbon::parse($date)->format('d/m/Y') : '',
                    'pos_bank' => optional($transaction->site)->bank,
                    'cash' => $cash,
                    'swipe' => $swipe,
                    'transfers' => $transfers,
                    'mpos' => $mpos,
                    'total_receipts' => $cash + $swipe + $transfers + $mpos,
                    'cash_balance' => (float) $transaction->{$fields['cash_balance']},
                    'bank' => $transaction->bank,
                    'cash_deposited' => (float) $transaction->{$fields['cash_deposited']},
                    'pos_id' => $transaction->POS ?: optional($transaction->site)->POS,
                ];
            })
            ->values();
    }

    private function buildDailyOnlyCashRows($sites, $transactions, string $currency, Carbon $date)
    {
        $fields = $currency === 'USD'
            ? [
                'cash' => 'usd_cash',
                'swipe' => 'usd_swipe',
                'transfers' => 'usd_transfers',
                'mpos' => 'usd_mpos',
                'cash_balance' => 'usd_cash_in_hand_balance',
                'cash_deposited' => 'usd_total_deposited',
            ]
            : [
                'cash' => 'zwg_cash',
                'swipe' => 'zwg_swipe',
                'transfers' => 'zwg_transfers',
                'mpos' => 'zwg_mpos',
                'cash_balance' => 'zwg_cash_in_hand_balance',
                'cash_deposited' => 'zwg_total_deposited',
            ];

        $transactionsBySite = $transactions->groupBy(fn ($transaction) => (string) $transaction->siteid);
        $dateLabel = $date->format('d/m/Y');

        return $sites
            ->map(function ($site) use ($transactionsBySite, $fields, $dateLabel) {
                $siteTransactions = $transactionsBySite->get((string) $site->id, collect());
                $firstTransaction = $siteTransactions->first();
                $cash = (float) $siteTransactions->sum($fields['cash']);
                $swipe = (float) $siteTransactions->sum($fields['swipe']);
                $transfers = (float) $siteTransactions->sum($fields['transfers']);
                $mpos = (float) $siteTransactions->sum($fields['mpos']);

                return (object) [
                    'site_name' => $site->site_name,
                    'platform_name' => $site->platform_name,
                    'sbu' => $site->sbu,
                    'username' => $siteTransactions->pluck('username')->filter()->unique()->join(', '),
                    'date_label' => $dateLabel,
                    'pos_bank' => $site->bank,
                    'cash' => $cash,
                    'swipe' => $swipe,
                    'transfers' => $transfers,
                    'mpos' => $mpos,
                    'total_receipts' => $cash + $swipe + $transfers + $mpos,
                    'cash_balance' => (float) $siteTransactions->sum($fields['cash_balance']),
                    'bank' => $firstTransaction?->bank,
                    'cash_deposited' => (float) $siteTransactions->sum($fields['cash_deposited']),
                    'pos_id' => $firstTransaction?->POS ?: $site->POS,
                ];
            })
            ->values();
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

    public function pamba()
    {
    }
}

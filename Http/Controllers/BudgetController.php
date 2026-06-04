<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\DailyCollection;
use App\Models\Network;
use App\Models\site as Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    private const VIEW_MODE_MONTHLY = 'monthly';
    private const VIEW_MODE_YTD = 'ytd';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        [$year, $month, $networkId, $viewMode] = $this->resolveFilters($request);
        $periodLabel = $this->buildPeriodLabel($year, $month, $viewMode);

        $networks = Network::orderBy('name')->get();
        $comparisonRows = $this->getComparisonRows($year, $month, $networkId, $viewMode);

        $budgets = Budget::with(['site.network'])
            ->whereNotNull('site_id')
            ->where('year', $year)
            ->when($viewMode === self::VIEW_MODE_MONTHLY, fn ($query) => $query->where('month', $month))
            ->when($viewMode === self::VIEW_MODE_YTD, fn ($query) => $query->whereBetween('month', [1, $month]))
            ->when($networkId, function ($query, $networkId) {
                $query->whereHas('site', function ($siteQuery) use ($networkId) {
                    $siteQuery->where('network_id', $networkId);
                });
            })
            ->orderBy('site_id')
            ->get();

        $summaryCards = [
            [
                'label' => 'Sites In Scope',
                'value' => number_format($comparisonRows->count()),
                'note' => 'Sites included in the selected reporting scope.',
                'icon' => 'bi bi-building',
            ],
            [
                'label' => 'Budget USD',
                'value' => '$' . number_format((float) $comparisonRows->sum('budgeted_amount_usd'), 2),
                'note' => 'USD targets captured for ' . strtolower($periodLabel) . '.',
                'icon' => 'bi bi-cash-stack',
            ],
            [
                'label' => 'Actual USD',
                'value' => '$' . number_format((float) $comparisonRows->sum('actual_usd'), 2),
                'note' => 'Actual USD insurance collections recorded for ' . strtolower($periodLabel) . '.',
                'icon' => 'bi bi-graph-up-arrow',
            ],
            [
                'label' => 'Budget ZWG',
                'value' => 'ZWG ' . number_format((float) $comparisonRows->sum('budgeted_amount_zwg'), 2),
                'note' => 'ZWG targets captured for ' . strtolower($periodLabel) . '.',
                'icon' => 'bi bi-wallet2',
            ],
            [
                'label' => 'Actual ZWG',
                'value' => 'ZWG ' . number_format((float) $comparisonRows->sum('actual_zwg'), 2),
                'note' => 'Actual ZWG insurance collections recorded for ' . strtolower($periodLabel) . '.',
                'icon' => 'bi bi-bar-chart-line',
            ],
            [
                'label' => 'Sites On Target',
                'value' => number_format($comparisonRows->where('is_on_target', true)->count()),
                'note' => 'Sites where actual USD or ZWG is at or above the target.',
                'icon' => 'bi bi-bullseye',
            ],
        ];

        $budgetComparisonData = [
            'labels' => $comparisonRows->pluck('site_name')->toArray(),
            'budgetedUsd' => $comparisonRows->pluck('budgeted_amount_usd')->map(fn ($value) => (float) $value)->toArray(),
            'actualUsd' => $comparisonRows->pluck('actual_usd')->map(fn ($value) => (float) $value)->toArray(),
            'budgetedZwg' => $comparisonRows->pluck('budgeted_amount_zwg')->map(fn ($value) => (float) $value)->toArray(),
            'actualZwg' => $comparisonRows->pluck('actual_zwg')->map(fn ($value) => (float) $value)->toArray(),
        ];

        return view('budgets.index', compact(
            'budgets',
            'networks',
            'year',
            'month',
            'networkId',
            'viewMode',
            'periodLabel',
            'comparisonRows',
            'summaryCards',
            'budgetComparisonData'
        ));
    }

    public function create(Request $request)
    {
        [$year, $month, $networkId] = $this->resolveFilters($request);
        $networks = Network::orderBy('name')->get();
        $sites = $this->getSitesForBudgetEntry($networkId);
        $existingBudgets = Budget::whereNotNull('site_id')
            ->where('year', $year)
            ->where('month', $month)
            ->when($networkId, function ($query, $networkId) {
                $query->whereHas('site', function ($siteQuery) use ($networkId) {
                    $siteQuery->where('network_id', $networkId);
                });
            })
            ->get()
            ->keyBy(fn ($budget) => (string) $budget->site_id);
        $actualCollections = $this->getActualCollectionMap($year, $month, $networkId);

        return view('budgets.create', compact(
            'networks',
            'sites',
            'year',
            'month',
            'networkId',
            'existingBudgets',
            'actualCollections'
        ));
    }

    public function usdChart(Request $request)
    {
        return $this->renderChartPage($request, 'usd');
    }

    public function zwgChart(Request $request)
    {
        return $this->renderChartPage($request, 'zwg');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:' . (Carbon::now()->year - 5) . '|max:' . (Carbon::now()->year + 5),
            'month' => 'required|integer|min:1|max:12',
            'network_id' => 'nullable|exists:network,id',
            'budgets' => 'required|array|min:1',
            'budgets.*.site_id' => 'required|exists:site,id',
            'budgets.*.budgeted_amount_usd' => 'nullable|numeric|min:0',
            'budgets.*.budgeted_amount_zwg' => 'nullable|numeric|min:0',
        ]);

        $siteIds = collect($validated['budgets'])->pluck('site_id')->unique()->values();
        $sites = Site::whereIn('id', $siteIds)->get()->keyBy(fn ($site) => (string) $site->id);

        foreach ($validated['budgets'] as $row) {
            $site = $sites->get((string) $row['site_id']);
            if (!$site) {
                continue;
            }

            Budget::updateOrCreate(
                [
                    'year' => $validated['year'],
                    'month' => $validated['month'],
                    'site_id' => $site->id,
                ],
                [
                    'network_id' => $site->network_id,
                    'budgeted_amount_usd' => $row['budgeted_amount_usd'] ?? 0,
                    'budgeted_amount_zwg' => $row['budgeted_amount_zwg'] ?? 0,
                ]
            );
        }

        return redirect()
            ->route('budgets.index', [
                'year' => $validated['year'],
                'month' => $validated['month'],
                'network_id' => $validated['network_id'],
            ])
            ->with('success', 'Monthly site budgets saved successfully.');
    }

    public function show(Budget $budget)
    {
        return redirect()->route('budgets.edit', $budget);
    }

    public function edit(Budget $budget)
    {
        $actualCollections = $this->getActualCollectionMap($budget->year, $budget->month, $budget->network_id);
        $currentActuals = $actualCollections->get((string) $budget->site_id);
        $networks = Network::orderBy('name')->get();
        $sites = $this->getSitesForBudgetEntry($budget->network_id);

        return view('budgets.edit', compact('budget', 'networks', 'sites', 'currentActuals'));
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:' . (Carbon::now()->year - 5) . '|max:' . (Carbon::now()->year + 5),
            'month' => 'required|integer|min:1|max:12',
            'site_id' => 'required|exists:site,id',
            'budgeted_amount_usd' => 'nullable|numeric|min:0',
            'budgeted_amount_zwg' => 'nullable|numeric|min:0',
        ]);

        $site = Site::findOrFail($validated['site_id']);

        $budget->update([
            'year' => $validated['year'],
            'month' => $validated['month'],
            'site_id' => $site->id,
            'network_id' => $site->network_id,
            'budgeted_amount_usd' => $validated['budgeted_amount_usd'] ?? 0,
            'budgeted_amount_zwg' => $validated['budgeted_amount_zwg'] ?? 0,
        ]);

        return redirect()
            ->route('budgets.index', [
                'year' => $validated['year'],
                'month' => $validated['month'],
                'network_id' => $site->network_id,
            ])
            ->with('success', 'Site budget updated successfully.');
    }

    public function destroy(Budget $budget)
    {
        $redirectFilters = [
            'year' => $budget->year,
            'month' => $budget->month,
            'network_id' => $budget->network_id,
        ];

        $budget->delete();

        return redirect()
            ->route('budgets.index', $redirectFilters)
            ->with('success', 'Site budget deleted successfully.');
    }

    private function resolveFilters(Request $request): array
    {
        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);
        $networkId = $request->input('network_id');
        $viewMode = $request->input('view_mode', self::VIEW_MODE_MONTHLY);

        if (!in_array($viewMode, [self::VIEW_MODE_MONTHLY, self::VIEW_MODE_YTD], true)) {
            $viewMode = self::VIEW_MODE_MONTHLY;
        }

        return [$year, $month, $networkId, $viewMode];
    }

    private function buildPeriodLabel(int $year, int $month, string $viewMode): string
    {
        $selectedMonth = Carbon::create($year, $month, 1)->format('F Y');

        if ($viewMode === self::VIEW_MODE_YTD) {
            return 'Year-to-Date through ' . $selectedMonth;
        }

        return 'Monthly view for ' . $selectedMonth;
    }

    private function getSitesForBudgetEntry($networkId)
    {
        return Site::with('network')
            ->when($networkId, function ($query) use ($networkId) {
                $query->where('network_id', $networkId);
            })
            ->orderBy('site_name')
            ->get();
    }

    private function getActualCollectionMap($year, $month, $networkId, string $viewMode = self::VIEW_MODE_MONTHLY)
    {
        return DailyCollection::select(
            'siteid',
            DB::raw('MAX(site_name) as site_name'),
            DB::raw('SUM(insurance_transactions) as actual_usd'),
            DB::raw('SUM(zwg_insurance_transactions) as actual_zwg')
        )
            ->whereYear('created_at', $year)
            ->when(
                $viewMode === self::VIEW_MODE_MONTHLY,
                fn ($query) => $query->whereMonth('created_at', $month),
                fn ($query) => $query->whereMonth('created_at', '<=', $month)
            )
            ->when($networkId, function ($query) use ($networkId) {
                $query->where('networkid', $networkId);
            })
            ->groupBy('siteid')
            ->get()
            ->keyBy(fn ($row) => (string) $row->siteid);
    }

    private function getComparisonRows($year, $month, $networkId, string $viewMode = self::VIEW_MODE_MONTHLY)
    {
        $sites = $this->getSitesForBudgetEntry($networkId)->keyBy(fn ($site) => (string) $site->id);
        $budgets = Budget::with(['site.network'])
            ->whereNotNull('site_id')
            ->where('year', $year)
            ->when($viewMode === self::VIEW_MODE_MONTHLY, fn ($query) => $query->where('month', $month))
            ->when($viewMode === self::VIEW_MODE_YTD, fn ($query) => $query->whereBetween('month', [1, $month]))
            ->when($networkId, function ($query, $networkId) {
                $query->whereHas('site', function ($siteQuery) use ($networkId) {
                    $siteQuery->where('network_id', $networkId);
                });
            })
            ->get()
            ->groupBy(fn ($budget) => (string) $budget->site_id);
        $actualCollections = $this->getActualCollectionMap($year, $month, $networkId, $viewMode);

        $siteIds = collect()
            ->merge($sites->keys())
            ->merge($budgets->keys())
            ->merge($actualCollections->keys())
            ->filter()
            ->unique()
            ->values();

        return $siteIds->map(function ($siteId) use ($sites, $budgets, $actualCollections) {
            $site = $sites->get((string) $siteId);
            $siteBudgets = $budgets->get((string) $siteId, collect());
            $budget = $siteBudgets->sortByDesc('month')->first();
            $actual = $actualCollections->get((string) $siteId);

            $siteName = $site->site_name
                ?? optional($budget?->site)->site_name
                ?? $actual->site_name
                ?? 'Unknown Site';

            $networkName = optional($site?->network)->name
                ?? optional($budget?->site?->network)->name
                ?? 'Unassigned';

            $budgetedUsd = (float) $siteBudgets->sum('budgeted_amount_usd');
            $budgetedZwg = (float) $siteBudgets->sum('budgeted_amount_zwg');
            $actualUsd = (float) ($actual->actual_usd ?? 0);
            $actualZwg = (float) ($actual->actual_zwg ?? 0);

            return [
                'budget_id' => $budget->id ?? null,
                'site_id' => $siteId,
                'site_name' => $siteName,
                'network_name' => $networkName,
                'budgeted_amount_usd' => $budgetedUsd,
                'budgeted_amount_zwg' => $budgetedZwg,
                'actual_usd' => $actualUsd,
                'actual_zwg' => $actualZwg,
                'variance_usd' => $actualUsd - $budgetedUsd,
                'variance_zwg' => $actualZwg - $budgetedZwg,
                'is_on_target' => $actualUsd >= $budgetedUsd || $actualZwg >= $budgetedZwg,
            ];
        })
        ->sortBy('site_name')
        ->values();
    }

    private function renderChartPage(Request $request, string $currency)
    {
        [$year, $month, $networkId, $viewMode] = $this->resolveFilters($request);
        $periodLabel = $this->buildPeriodLabel($year, $month, $viewMode);
        $networks = Network::orderBy('name')->get();
        $comparisonRows = $this->getComparisonRows($year, $month, $networkId, $viewMode);

        if ($currency === 'usd') {
            $chartTitle = 'USD Budget Vs Actual';
            $chartDescription = 'Horizontal bars comparing each site budget against actual USD collections for the selected reporting period.';
            $budgetLabel = 'Budget USD';
            $actualLabel = 'Actual USD';
            $budgetColor = 'rgba(15, 107, 110, 0.82)';
            $actualColor = 'rgba(143, 199, 187, 0.88)';
            $chartId = 'budgetActualUsdChartPage';
            $budgetData = $comparisonRows->pluck('budgeted_amount_usd')->map(fn ($value) => (float) $value)->toArray();
            $actualData = $comparisonRows->pluck('actual_usd')->map(fn ($value) => (float) $value)->toArray();
            $varianceKey = 'variance_usd';
            $valuePrefix = '$';
            $valueSuffix = '';
            $totalBudget = (float) $comparisonRows->sum('budgeted_amount_usd');
            $totalActual = (float) $comparisonRows->sum('actual_usd');
        } else {
            $chartTitle = 'ZWG Budget Vs Actual';
            $chartDescription = 'Horizontal bars comparing each site budget against actual ZWG collections for the selected reporting period.';
            $budgetLabel = 'Budget ZWG';
            $actualLabel = 'Actual ZWG';
            $budgetColor = 'rgba(217, 119, 69, 0.82)';
            $actualColor = 'rgba(240, 181, 109, 0.9)';
            $chartId = 'budgetActualZwgChartPage';
            $budgetData = $comparisonRows->pluck('budgeted_amount_zwg')->map(fn ($value) => (float) $value)->toArray();
            $actualData = $comparisonRows->pluck('actual_zwg')->map(fn ($value) => (float) $value)->toArray();
            $varianceKey = 'variance_zwg';
            $valuePrefix = 'ZWG ';
            $valueSuffix = '';
            $totalBudget = (float) $comparisonRows->sum('budgeted_amount_zwg');
            $totalActual = (float) $comparisonRows->sum('actual_zwg');
        }

        $summaryCards = [
            [
                'label' => 'Sites In Scope',
                'value' => number_format($comparisonRows->count()),
                'note' => 'Sites represented in this comparison page.',
                'icon' => 'bi bi-building',
            ],
            [
                'label' => $budgetLabel,
                'value' => $valuePrefix . number_format($totalBudget, 2) . $valueSuffix,
                'note' => 'Total budget in the current ' . strtolower($periodLabel) . '.',
                'icon' => 'bi bi-bullseye',
            ],
            [
                'label' => $actualLabel,
                'value' => $valuePrefix . number_format($totalActual, 2) . $valueSuffix,
                'note' => 'Total actual collections in the current ' . strtolower($periodLabel) . '.',
                'icon' => 'bi bi-graph-up-arrow',
            ],
            [
                'label' => 'Sites On Target',
                'value' => number_format($comparisonRows->filter(fn ($row) => $row[$varianceKey] >= 0)->count()),
                'note' => 'Sites where actual is at or above budget for this currency.',
                'icon' => 'bi bi-check2-circle',
            ],
        ];

        $chartData = [
            'labels' => $comparisonRows->pluck('site_name')->toArray(),
            'budget' => $budgetData,
            'actual' => $actualData,
        ];

        return view('budgets.chart', compact(
            'year',
            'month',
            'networkId',
            'viewMode',
            'periodLabel',
            'networks',
            'comparisonRows',
            'summaryCards',
            'chartTitle',
            'chartDescription',
            'budgetLabel',
            'actualLabel',
            'budgetColor',
            'actualColor',
            'chartId',
            'chartData',
            'varianceKey',
            'valuePrefix',
            'valueSuffix',
            'currency'
        ));
    }
}

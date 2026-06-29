<?php

namespace App\Http\Controllers;

use App\Models\CourierSale;
use App\Models\FaceValue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourierSaleController extends Controller
{
    private const COURIER_SBU = 'SBU3';
    private const COURIER_PLATFORM = 'Courier Connect';
    private const INSURANCE_PROVIDERS = ['Nicoz Diamond', 'Champions'];
    private const SUPPORTED_CURRENCIES = ['USD', 'ZWG'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $isCourierClerk = $roleId === 7;
        $isCourierSupervisor = $roleId === 6 || ($roleId === 3 && $this->isCourierSbu($this->getUserSbu($user)));
        $isGlobalViewer = in_array($roleId, [1, 5], true);

        abort_unless($isCourierClerk || $isCourierSupervisor || $isGlobalViewer, 403);

        return $this->renderReport($request, [
            'isCourierClerk' => $isCourierClerk,
            'isCourierSupervisor' => $isCourierSupervisor,
            'isGlobalViewer' => $isGlobalViewer,
            'scope' => $isCourierClerk
                ? ['clerk_id' => $user->id]
                : ($isCourierSupervisor ? ['clerk_ids' => $this->getCourierClerkIds()] : []),
            'routeName' => 'courier.sales.index',
        ]);
    }

    public function superuser(Request $request)
    {
        abort_unless((int) Auth::user()->role_id === 5, 403);

        return $this->renderReport($request, [
            'isCourierClerk' => false,
            'isCourierSupervisor' => false,
            'isGlobalViewer' => true,
            'scope' => [],
            'routeName' => 'courier.sales.superuser',
        ]);
    }

    public function supervisor(Request $request)
    {
        abort_unless((int) Auth::user()->role_id === 3, 403);

        return $this->renderReport($request, [
            'isCourierClerk' => false,
            'isCourierSupervisor' => true,
            'isGlobalViewer' => false,
            'scope' => [],
            'routeName' => 'courier.sales.supervisor',
        ]);
    }

    private function renderReport(Request $request, array $options)
    {
        $isCourierClerk = (bool) ($options['isCourierClerk'] ?? false);
        $isCourierSupervisor = (bool) ($options['isCourierSupervisor'] ?? false);
        $isGlobalViewer = (bool) ($options['isGlobalViewer'] ?? false);
        $scope = $options['scope'] ?? [];
        $routeName = $options['routeName'] ?? 'courier.sales.index';

        $selectedDate = $request->filled('sale_date')
            ? Carbon::parse($request->input('sale_date'))->toDateString()
            : Carbon::today()->toDateString();
        $selectedProvider = $request->input('insurance_provider');
        $selectedClerkId = $request->input('clerk_id');

        $batches = !empty($scope['clerk_id']) ? $this->getCourierBatchesForClerk($scope['clerk_id']) : collect();

        $query = CourierSale::with(['clerk.site', 'clerk.network', 'faceValue'])
            ->select('courier_sales.*')
            ->selectSub(function ($usageQuery) {
                $usageQuery->from('face_values')
                    ->selectRaw('COALESCE(SUM(used), 0)')
                    ->whereColumn('face_values.clerk_id', 'courier_sales.clerk_id')
                    ->whereColumn('face_values.batch_id', 'courier_sales.batch_id')
                    ->where('face_values.is_parent', false)
                    ->whereRaw('DATE(face_values.created_at) = courier_sales.sale_date')
                    ->where(function ($providerQuery) {
                        $providerQuery->whereColumn('face_values.insurance_provider', 'courier_sales.insurance_provider')
                            ->orWhereNull('face_values.insurance_provider');
                    });
            }, 'face_values_used')
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at');

        if (!empty($scope['clerk_id'])) {
            $query->where('clerk_id', $scope['clerk_id']);
        } elseif (!empty($scope['clerk_ids'])) {
            $query->whereIn('clerk_id', $scope['clerk_ids']);
        }

        if ($request->filled('sale_date')) {
            $query->whereDate('sale_date', $selectedDate);
        }

        if ($selectedProvider) {
            $query->where('insurance_provider', $selectedProvider);
        }

        if ($selectedClerkId) {
            $query->where('clerk_id', $selectedClerkId);
        }

        $sales = $query->get();
        $faceValuesUsedTotal = $this->getFaceValuesUsedTotal($sales);

        $summaryCards = [
            [
                'label' => 'Sales Entries',
                'value' => number_format($sales->count()),
                'note' => 'Courier Connect sales records in the current view.',
                'icon' => 'bi bi-journal-check',
            ],
            [
                'label' => 'USD Sales',
                'value' => '$' . number_format((float) $sales->where('currency', 'USD')->sum('sales_amount'), 2),
                'note' => 'Courier Connect sales posted in USD.',
                'icon' => 'bi bi-cash-stack',
            ],
            [
                'label' => 'ZWG Sales',
                'value' => 'ZWG ' . number_format((float) $sales->where('currency', 'ZWG')->sum('sales_amount'), 2),
                'note' => 'Courier Connect sales posted in ZWG.',
                'icon' => 'bi bi-wallet2',
            ],
            [
                'label' => 'Providers',
                'value' => number_format($sales->pluck('insurance_provider')->filter()->unique()->count()),
                'note' => 'Insurers represented in the selected Courier sales.',
                'icon' => 'bi bi-building-check',
            ],
            [
                'label' => 'Face Values Used',
                'value' => number_format($faceValuesUsedTotal),
                'note' => 'Declared face values used for the selected Courier sales.',
                'icon' => 'bi bi-receipt-cutoff',
            ],
        ];

        $clerks = ($isCourierSupervisor || $isGlobalViewer)
            ? $this->getClerksForSalesReport($scope)
            : collect();

        return view('courier-sales.index', [
            'sales' => $sales,
            'batches' => $batches,
            'clerks' => $clerks,
            'selectedDate' => $selectedDate,
            'selectedProvider' => $selectedProvider,
            'selectedClerkId' => $selectedClerkId,
            'insuranceProviders' => self::INSURANCE_PROVIDERS,
            'currencies' => self::SUPPORTED_CURRENCIES,
            'summaryCards' => $summaryCards,
            'isCourierClerk' => $isCourierClerk,
            'isCourierSupervisor' => $isCourierSupervisor,
            'reportRouteName' => $routeName,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless((int) Auth::user()->role_id === 7, 403);

        $request->validate([
            'face_value_id' => 'required|integer',
            'batch_id' => 'required|integer',
            'insurance_provider' => 'required|in:' . implode(',', self::INSURANCE_PROVIDERS),
            'currency' => 'required|in:' . implode(',', self::SUPPORTED_CURRENCIES),
            'sales_amount' => 'required|numeric|min:0.01',
            'sale_date' => 'required|date',
            'comments' => 'nullable|string',
        ]);

        $parentFaceValue = FaceValue::where('id', $request->input('face_value_id'))
            ->where('batch_id', $request->input('batch_id'))
            ->where('clerk_id', Auth::id())
            ->where('is_parent', true)
            ->first();

        if (!$parentFaceValue) {
            return back()->withInput()->with('error', 'The selected face value batch could not be found for your account.');
        }

        CourierSale::create([
            'face_value_id' => $parentFaceValue->id,
            'batch_id' => $parentFaceValue->batch_id,
            'clerk_id' => Auth::id(),
            'supervisor_id' => $parentFaceValue->supervisor_id,
            'network_id' => Auth::user()->networkid,
            'site_id' => Auth::user()->siteid,
            'insurance_provider' => $request->input('insurance_provider'),
            'currency' => $request->input('currency'),
            'sales_amount' => $request->input('sales_amount'),
            'sale_date' => Carbon::parse($request->input('sale_date'))->toDateString(),
            'comments' => $request->input('comments'),
        ]);

        return redirect()->route('courier.sales.index')->with('success', 'Courier Connect sale submitted successfully.');
    }

    private function normalizeSbu(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim($value)));

        return $normalized !== '' ? $normalized : null;
    }

    private function getUserSbu(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        if ($user->site && $user->site->sbu) {
            return $this->normalizeSbu($user->site->sbu);
        }

        if ($user->network && $user->network->name) {
            return $this->normalizeSbu($user->network->name);
        }

        return null;
    }

    private function isCourierSbu(?string $sbu): bool
    {
        return $sbu === self::COURIER_SBU;
    }

    private function getCourierClerkIds(): array
    {
        return User::whereIn('role_id', [2, 7])
            ->whereHas('site', function ($siteQuery) {
                $siteQuery->whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)]);
            })
            ->pluck('id')
            ->toArray();
    }

    private function getCourierBatchesForClerk(int $clerkId)
    {
        return FaceValue::where('clerk_id', $clerkId)
            ->where('is_parent', true)
            ->where('batch_balance', '>', 0)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('batch_id')
            ->map(fn ($rows) => $rows->sortByDesc('created_at')->first())
            ->sortByDesc('created_at')
            ->values();
    }

    private function getClerksForSalesReport(array $scope)
    {
        $query = User::with('site')->orderBy('name');

        if (!empty($scope['clerk_ids'])) {
            $query->whereIn('id', $scope['clerk_ids']);
        } else {
            $query->whereIn('id', CourierSale::query()
                ->select('clerk_id')
                ->whereNotNull('clerk_id')
                ->distinct());
        }

        return $query->get();
    }

    private function getFaceValuesUsedTotal(Collection $sales): int
    {
        return (int) $sales
            ->filter(fn (CourierSale $sale) => $sale->clerk_id && $sale->batch_id && $sale->sale_date)
            ->unique(function (CourierSale $sale) {
                return implode('|', [
                    $sale->clerk_id,
                    $sale->batch_id,
                    optional($sale->sale_date)->toDateString(),
                    $sale->insurance_provider,
                ]);
            })
            ->sum(fn (CourierSale $sale) => (float) $sale->face_values_used);
    }
}

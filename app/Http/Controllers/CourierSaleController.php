<?php

namespace App\Http\Controllers;

use App\Models\CourierSale;
use App\Models\FaceValue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourierSaleController extends Controller
{
    private const COURIER_SBU = 'SBU3';
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

        $selectedDate = $request->filled('sale_date')
            ? Carbon::parse($request->input('sale_date'))->toDateString()
            : Carbon::today()->toDateString();
        $selectedProvider = $request->input('insurance_provider');
        $selectedClerkId = $request->input('clerk_id');

        $batches = $isCourierClerk ? $this->getCourierBatchesForClerk($user->id) : collect();

        $query = CourierSale::with(['clerk.site', 'clerk.network', 'faceValue'])
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at');

        if ($isCourierClerk) {
            $query->where('clerk_id', $user->id);
        } elseif ($isCourierSupervisor) {
            $query->whereIn('clerk_id', $this->getCourierClerkIds());
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
        ];

        $clerks = ($isCourierSupervisor || $isGlobalViewer)
            ? User::with('site')
                ->whereIn('id', $this->getCourierClerkIds())
                ->orderBy('name')
                ->get()
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
        return User::where('role_id', 7)
            ->where(function ($query) {
                $query->whereHas('site', function ($siteQuery) {
                    $siteQuery->where('sbu', self::COURIER_SBU);
                });
                $query->orWhereHas('network', function ($networkQuery) {
                    $networkQuery->where('name', self::COURIER_SBU);
                });
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
}

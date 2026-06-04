<?php

namespace App\Http\Controllers;
use Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\DailyCollection; 
use App\Models\FaceValue;
use App\Models\FaceValueUsage;
use App\Models\User; 
use App\Models\CashInHandBalance; 
use App\Models\CollectionAmmendments;
use App\Models\Site;  // Add this import


use Carbon\Carbon;

class DailyCollectionController extends Controller
{
    private const COURIER_SBU = 'SBU3';

    
     public function __construct()
    {
        $this->middleware('auth');
    }
    public function transactions()
    {


        $userId = Auth::id();
        $user = User::find($userId);
    
        if ($user->role->id == 3) {
            $sql = DailyCollection::orderBy('created_at', 'desc')->get();
        } else {
            $sql = DailyCollection::where('user_id', $userId)->orderBy('created_at', 'desc')->get(); 
        }
    //dd($sql);
    
        return view('collection.transactions', compact('sql'));
        
    }
    public function create()
    {
        $user = Auth::user();

        return view('collection.create', [
            'isCourierClerk' => $this->isCourierClerk($user),
            'courierFaceValueBatches' => $this->getActiveCourierFaceValueBatches($user?->id),
        ]);
    }

   public function store(Request $request)
{
    $validated = $request->validate([
        'face_value_usage' => 'nullable|array',
        'face_value_usage.*.fvid' => 'nullable|integer',
        'face_value_usage.*.batch_id' => 'nullable|integer',
        'face_value_usage.*.used' => 'nullable|integer|min:0',
        'face_value_usage.*.spoiled' => 'nullable|integer|min:0',
        'face_value_usage.*.comments' => 'nullable|string',
    ]);

    $id = auth()->id();
    $user = User::with('site', 'network')->findOrFail($id);
    $isCourierClerk = $this->isCourierClerk($user);
    $usageEntries = $this->extractCourierUsageEntries($validated['face_value_usage'] ?? []);
    $today = now()->format('Y-m-d');

    $existingSubmission = DailyCollection::where('user_id', $id)
        ->whereDate('created_at', $today)
        ->exists();
        
$submissionCount = DailyCollection::where('user_id', $id)
        ->whereDate('created_at', $today)
        ->count();
          $now = Carbon::now();
    $hour = $now->hour;

    $after4pmSubmission = DailyCollection::where('user_id', $id)
    ->whereDate('created_at', $today)
    ->where('created_at', '>=', Carbon::today()->setHour(16)->setMinute(0)->setSecond(0))
    ->exists();
 /*   
    if ($hour < 10) {
        if ($submissionCount >= 1) {
            return redirect()->back()->with('error', 'You can only make one submission before 10 AM.');
        }
    } elseif ($hour >= 16) {
        if ($submissionCount >= 2) {
            return redirect()->back()->with('error', 'You can only make up to two submissions after 4 PM.');
        }
    } else {
        return redirect()->back()->with('error', 'Submissions are only allowed before 9 AM or after 4 PM.');
    }
    
    if ($after4pmSubmission) {
    return back()->with('error', 'A submission was already done after 4PM today.');
}*/
    $totalCollections = ((float) $request->input('insurance_transactions')) + ((float) $request->input('zwg_insurance_transactions'));

    if ($isCourierClerk && $totalCollections > 0 && $usageEntries->isEmpty()) {
        return back()
            ->withInput()
            ->with('error', 'Courier Connect sales must be linked to at least one issued face value batch before submission.');
    }

    try {
        $sql = DB::transaction(function () use ($request, $id, $user, $usageEntries) {
            $cihbalance = CashInHandBalance::where('clerk_id', $id)->first();

            $usdcollectioncih = ((float) $request->input('usd_cash')) - ((float) $request->input('usd_total_deposited'));
            $zwgcollectioncih = ((float) $request->input('zwg_cash')) - ((float) $request->input('zwg_total_deposited'));

            if (!$cihbalance) {
                $usdcihbalance = $usdcollectioncih;
                $zwgcihbalance = $zwgcollectioncih;

                CashInHandBalance::create([
                    'clerk_id' => $id,
                    'balance_usd' => $usdcihbalance,
                    'balance_zwg' => $zwgcihbalance
                ]);
            } else {
                $usdcihbalance = $cihbalance->balance_usd + $usdcollectioncih;
                $zwgcihbalance = $cihbalance->balance_zwg + $zwgcollectioncih;

                $cihbalance->update([
                    'balance_usd' => $usdcihbalance,
                    'balance_zwg' => $zwgcihbalance
                ]);
            }

            $username = $user->name;
            $site = $user->siteid;
            $network = $user->networkid;
            $platform = $user->site->platform_name ?? null;
            $code = $user->site->code ?? null;
            $sitename = $user->site->site_name ?? null;
            $pos = $user->site->POS ?? null;

            $collection = DailyCollection::create([
                'currency'=> $request->input('currency'),
                'insurance_transactions'=> $request->input('insurance_transactions'),
                'zwg_insurance_transactions'=> $request->input('zwg_insurance_transactions'),
                'zinara_transactions'=> $request->input('zinara_transactions'),
                'zwg_cash' => $request->input('zwg_cash'),
                'zwg_mpos' => $request->input('zwg_mpos'),
                'usd_mpos' => $request->input('usd_mpos'),
                'zwg_swipe' =>  $request->input('zwg_swipe'),
                'zwg_transfers' =>$request->input('zwg_transfers'),
                'zwg_third_party_premiums' => $request->input('zwg_third_party_premiums'),
                'zwg_full_cover_premiums' =>  $request->input('zwg_full_cover_premiums'),
                'zwg_zinara_fees' =>$request->input('zwg_zinara_fees'),
                'third_party_premiums' => $request->input('third_party_premiums'),
                'full_cover_premiums' =>  $request->input('full_cover_premiums'),
                'zinara_fees' =>$request->input('zinara_fees'),
                'usd_cash' => $request->input('usd_cash'),
                'usd_swipe' =>  $request->input('usd_swipe'),
                'usd_transfers' =>$request->input('usd_transfers'),
                'bank' => $request->input('bank'),
                'usd_total_deposited' =>  $request->input('usd_total_deposited'),
                'zwg_total_deposited' =>  $request->input('zwg_total_deposited'),
                'comments' =>  $request->input('comments'),
                'user_id' => $id,
                'username' => $username,
                'siteid' => $site,
                'networkid' => $network,
                'platform_name' => $platform,
                'code' => $code,
                'POS' => $pos,
                'site_name' => $sitename,
                'balance' => $request->input('balance'),
                'usd_debit_sales' =>$request->input('usd_debit_sales'),
                'usd_credit_sales' => $request->input('usd_credit_sales'),
                'zwg_debit_sales' =>  $request->input('zwg_debit_sales'),
                'zwg_credit_sales' =>$request->input('zwg_credit_sales'),
                'usd_cash_in_hand' =>$usdcollectioncih,
                'usd_cash_in_hand_balance' =>$usdcihbalance,
                'zwg_cash_in_hand' =>$zwgcollectioncih,
                'zwg_cash_in_hand_balance' =>$zwgcihbalance,
                'other_insurances_zwg' => $request->input('other_insurances_zwg'),
                'other_insurances_usd' => $request->input('other_insurances_usd'),
            ]);

            if ($usageEntries->isNotEmpty()) {
                $this->storeCourierFaceValueUsage($collection, $usageEntries, $user);
            }

            return $collection;
        });
    } catch (\Throwable $exception) {
        return back()
            ->withInput()
            ->with('error', $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'There was an error submitting the collection.');
    }
    
//dd($sql);

    $insuranceTransaction = $request->third_party_premiums + $request->full_cover_premiums + $request->zinara_fees;
    $zwginsuranceTransaction = $request->zwg_third_party_premiums + $request->zwg_full_cover_premiums + $request->zwg_zinara_fees;
    
    if ($sql) {
        return redirect()->back()->with('status', 'Collection submitted successfully!');
    } else {
        // If there's an error
        return redirect()->back()->with('error', 'There was an error submitting the collection.');
    }
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

    private function isCourierClerk(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return (int) $user->role_id === 7 || $this->getUserSbu($user) === self::COURIER_SBU;
    }

    private function getActiveCourierFaceValueBatches(?int $userId)
    {
        if (!$userId) {
            return collect();
        }

        return FaceValue::where('clerk_id', $userId)
            ->where('is_parent', true)
            ->where('batch_balance', '>', 0)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('batch_id')
            ->map(fn ($rows) => $rows->sortByDesc('created_at')->first())
            ->sortBy('batch_id')
            ->values();
    }

    private function extractCourierUsageEntries(array $entries)
    {
        return collect($entries)
            ->map(function ($entry) {
                $used = (int) ($entry['used'] ?? 0);
                $spoiled = (int) ($entry['spoiled'] ?? 0);

                return [
                    'fvid' => isset($entry['fvid']) ? (int) $entry['fvid'] : null,
                    'batch_id' => isset($entry['batch_id']) ? (int) $entry['batch_id'] : null,
                    'used' => $used,
                    'spoiled' => $spoiled,
                    'comments' => trim((string) ($entry['comments'] ?? '')),
                ];
            })
            ->filter(fn ($entry) => ($entry['used'] + $entry['spoiled']) > 0)
            ->values();
    }

    private function storeCourierFaceValueUsage(DailyCollection $collection, $usageEntries, User $user): void
    {
        foreach ($usageEntries as $entry) {
            $parentFaceValue = FaceValue::where('id', $entry['fvid'])
                ->where('batch_id', $entry['batch_id'])
                ->where('clerk_id', $collection->user_id)
                ->where('is_parent', true)
                ->lockForUpdate()
                ->first();

            if (!$parentFaceValue) {
                throw new \RuntimeException('One of the selected Courier face value batches could not be found.');
            }

            $alreadyDeclaredToday = FaceValue::where('clerk_id', $collection->user_id)
                ->where('is_parent', false)
                ->whereDate('created_at', Carbon::today())
                ->where('batch_id', $entry['batch_id'])
                ->exists();

            if ($alreadyDeclaredToday) {
                throw new \RuntimeException('A face value declaration for batch #' . $entry['batch_id'] . ' has already been submitted today.');
            }

            $availableBalance = (int) round((float) $parentFaceValue->batch_balance);
            $usedTotal = $entry['used'] + $entry['spoiled'];

            if ($usedTotal > $availableBalance) {
                throw new \RuntimeException('Face value usage for batch #' . $entry['batch_id'] . ' exceeds the available issued balance.');
            }

            $closingBalance = $availableBalance - $usedTotal;

            $parentFaceValue->update([
                'batch_balance' => $closingBalance,
            ]);

            FaceValue::create([
                'starting' => $parentFaceValue->starting,
                'ending' => $parentFaceValue->ending,
                'received' => 0,
                'used' => $entry['used'],
                'closing_balance' => $closingBalance,
                'opening_balance' => $availableBalance,
                'clerk_id' => $collection->user_id,
                'supervisor_id' => $parentFaceValue->supervisor_id,
                'batch_id' => $entry['batch_id'],
                'is_parent' => false,
                'parent_id' => $parentFaceValue->id,
                'spoiled' => $entry['spoiled'],
                'comments' => $entry['comments'] ?: 'Captured from Daily Collection submission.',
                'batch_balance' => $closingBalance,
                'siteid' => $collection->siteid,
                'networkid' => $collection->networkid,
                'daily_collection_id' => $collection->id,
            ]);

            FaceValueUsage::create([
                'daily_collection_id' => $collection->id,
                'batch_id' => $entry['batch_id'],
                'clerk_id' => $collection->user_id,
                'network_id' => $collection->networkid,
                'site_id' => $collection->siteid,
                'platform_name' => $collection->platform_name,
                'used' => $entry['used'],
                'spoiled' => $entry['spoiled'],
                'remaining' => $closingBalance,
                'usage_date' => optional($collection->created_at)->toDateString() ?? now()->toDateString(),
                'comments' => $entry['comments'] ?: null,
            ]);
        }
    }
    public function manage()
    {
    //$userId = Auth::id();
    //$sql = DailyCollection::where('user_id', $userId)->get();


    $userId = Auth::id();
    $user = User::find($userId);

    if ($user->role->id == 3) {
        $sql = DailyCollection::orderBy('created_at', 'desc')->get();
    } else {
        $sql = DailyCollection::where('user_id', $userId)->orderBy('created_at', 'desc')->get(); 
    }

   

    return view('collection.manage', compact('sql'));


    }

public function userReports(Request $request)
    {
        // Fetch all users for the dropdown filter
        // You might want to filter this list based on roles (e.g., only clerks)
        $users = User::orderBy('name')->get();

        // Initialize variables for the view
        $filteredTransactions = collect(); // Empty collection for initial load or no results
        $userLineChartLabels = [];
        $userLineChartUsdData = [];
        $userLineChartZwgData = [];
        $userBarChartLabels = [];
        $userBarChartUsdData = [];
        $userBarChartZwgData = [];

        // Check if filter parameters are provided (form submitted)
        if ($request->filled(['startdate', 'enddate', 'user_id'])) {
            $startDate = Carbon::parse($request->input('startdate'))->startOfDay();
            $endDate = Carbon::parse($request->input('enddate'))->endOfDay();
            $selectedUserId = $request->input('user_id');

            // Fetch transactions for the selected user within the date range
            $filteredTransactions = DailyCollection::where('user_id', $selectedUserId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'asc') // Order by date for line chart
                ->get();

            // --- Prepare data for User Line Chart (Daily USD & ZWG Transactions for selected user) ---
            $dailyUserUsdTransactions = $filteredTransactions
                ->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('Y-m-d'); // Group by date string
                })
                ->map(function ($row) {
                    return $row->sum('insurance_transactions');
                });

            $dailyUserZwgTransactions = $filteredTransactions
                ->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('Y-m-d');
                })
                ->map(function ($row) {
                    return $row->sum('zwg_insurance_transactions');
                });

            // Ensure common labels and align data for line chart
            $allDates = array_unique(array_merge($dailyUserUsdTransactions->keys()->toArray(), $dailyUserZwgTransactions->keys()->toArray()));
            sort($allDates);
            $userLineChartLabels = $allDates;

            foreach ($userLineChartLabels as $date) {
                $userLineChartUsdData[] = $dailyUserUsdTransactions->has($date) ? $dailyUserUsdTransactions[$date] : 0;
                $userLineChartZwgData[] = $dailyUserZwgTransactions->has($date) ? $dailyUserZwgTransactions[$date] : 0;
            }


            // --- Prepare data for User Bar Chart (Total USD/ZWG transactions per site for selected user) ---
            // This chart will show the selected user's transactions broken down by sites they worked at.
            $userSiteTransactions = $filteredTransactions
                ->groupBy('site_name')
                ->map(function ($row) {
                    return [
                        'total_usd_transactions' => $row->sum('insurance_transactions'),
                        'total_zwg_transactions' => $row->sum('zwg_insurance_transactions'),
                    ];
                });

            $userBarChartLabels = $userSiteTransactions->keys()->toArray();
            $userBarChartUsdData = $userSiteTransactions->pluck('total_usd_transactions')->toArray();
            $userBarChartZwgData = $userSiteTransactions->pluck('total_zwg_transactions')->toArray();

        }

        return view('Reports.user', compact(
            'users',
            'filteredTransactions',
            'userLineChartLabels',
            'userLineChartUsdData',
            'userLineChartZwgData',
            'userBarChartLabels',
            'userBarChartUsdData',
            'userBarChartZwgData'
        ));
    }
    
    
       // ammendments

public function ammendments(Request $request)
{
   
    $date = $request->has('date')
        ? Carbon::parse($request->input('date'))
        : Carbon::today();

   $sql = DailyCollection::whereDate('created_at', '=', $date->toDateString())->get();

    // Optionally check if data exists
    // if ($sql->isEmpty()) {
    //     return back()->with('warning', 'No records found for ' . $date);
    // }

    // Pass data and date to the view
    return view('collection.ammendments', compact('sql', 'date'));
}

    public function viewammendment($collectionid)
    {

    $userId = Auth::id();
    $user = User::find($userId);

   $sql = DailyCollection::find($collectionid);


    $user = User::find(Auth::id());
    return view('collection.viewammendment', compact('sql', 'user'));
        
    }

public function approveammendmentrequest(Request $request)
    {

try {
        DB::beginTransaction();

        // ✅ Find the related amendment record
        $ammendment = CollectionAmmendments::findOrFail($request->ammendment_id);

        // ✅ Find the DailyCollection record to update
        $dailyCollection = DailyCollection::findOrFail($request->transaction_id);
//dd($dailyCollection);
        // ✅ Prepare only the fields that exist in the DailyCollection model
        $updateData = $request->only([
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
            'code',
            'site_name',
            'zwg_debit_sales',
            'usd_debit_sales',
            'zwg_credit_sales',
            'usd_credit_sales',
        ]);

        // ✅ Update the Daily Collection record
        $dailyCollection->update($updateData);
//dd($updateData);
        // ✅ Update amendment status & approval info
        $ammendment->update([
            'status' => 'approved',
            'ammendmentapprovaldate' => now(),
            'approverid' => Auth::id(),
        ]);

        DB::commit();

        return redirect()
            ->route('dailycollection.ammendmentrequestlist')
            ->with('success', 'Amendment approved and Daily Collection updated successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error approving amendment: ' . $e->getMessage());
    }
    }


     public function ammendmentrequest(Request $request)
    {

   // dd($request->all());
   $checkammendment = CollectionAmmendments::where('transaction_id', $request->transaction_id)
   ->where('status', 'requested')
   ->first();
    if ($checkammendment) {
     return redirect()->back()->with('error', 'An amendment request for this transaction is already pending.');
    }
   // dd($request->all());
   // Create new record
        $ammendment = CollectionAmmendments::create([
            'transaction_id' => $request->transaction_id,
            'transaction_date' => $request->transaction_date,

            // OLD fields
            'currencyold' => $request->currencyold,
            'third_party_premiumsold' => $request->third_party_premiumsold,
            'full_cover_premiumsold' => $request->full_cover_premiumsold,
            'zinara_feesold' => $request->zinara_feesold,
            'usd_mposold' => $request->usd_mposold,
            'zwg_mposold' => $request->zwg_mposold,
            'zwg_third_party_premiumsold' => $request->zwg_third_party_premiumsold,
            'zwg_full_cover_premiumsold' => $request->zwg_full_cover_premiumsold,
            'zwg_zinara_feesold' => $request->zwg_zinara_feesold,
            'usd_total_depositedold' => $request->usd_total_depositedold,
            'zwg_total_depositedold' => $request->zwg_total_depositedold,
            'usd_cashold' => $request->usd_cashold,
            'usd_swipeold' => $request->usd_swipeold,
            'usd_transfersold' => $request->usd_transfersold,
            'zwg_cashold' => $request->zwg_cashold,
            'zwg_swipeold' => $request->zwg_swipeold,
            'zwg_transfersold' => $request->zwg_transfersold,
            'bankold' => $request->bankold,
            'cash_depositedold' => $request->cash_depositedold,
            'zwg_cash_depositedold' => $request->zwg_cash_depositedold,
            'commentsold' => $request->commentsold,
            'user_idold' => $request->user_idold,
            'insurance_transactionsold' => $request->insurance_transactionsold,
            'zwg_insurance_transactionsold' => $request->zwg_insurance_transactionsold,
            'zinara_transactionsold' => $request->zinara_transactionsold,
            'usernameold' => $request->usernameold,
            'networkidold' => $request->networkidold,
            'siteidold' => $request->siteidold,
            'balanceold' => $request->balanceold,
            'codeold' => $request->codeold,
            'site_nameold' => $request->site_nameold,
            'zwg_debit_salesold' => $request->zwg_debit_salesold,
            'usd_debit_salesold' => $request->usd_debit_salesold,
            'zwg_credit_salesold' => $request->zwg_credit_salesold,
            'usd_credit_salesold' => $request->usd_credit_salesold,

            // NEW fields
            'currency' => $request->currency,
            'third_party_premiums' => $request->third_party_premiums,
            'full_cover_premiums' => $request->full_cover_premiums,
            'zinara_fees' => $request->zinara_fees,
            'usd_mpos' => $request->usd_mpos,
            'zwg_mpos' => $request->zwg_mpos,
            'zwg_third_party_premiums' => $request->zwg_third_party_premiums,
            'zwg_full_cover_premiums' => $request->zwg_full_cover_premiums,
            'zwg_zinara_fees' => $request->zwg_zinara_fees,
            'usd_total_deposited' => $request->usd_total_deposited,
            'zwg_total_deposited' => $request->zwg_total_deposited,
            'usd_cash' => $request->usd_cash,
            'usd_swipe' => $request->usd_swipe,
            'usd_transfers' => $request->usd_transfers,
            'zwg_cash' => $request->zwg_cash,
            'zwg_swipe' => $request->zwg_swipe,
            'zwg_transfers' => $request->zwg_transfers,
            'bank' => $request->bank,
            'cash_deposited' => $request->cash_deposited,
            'zwg_cash_deposited' => $request->zwg_cash_deposited,
            'comments' => $request->comments,
            'user_id' => $request->user_id,
            'insurance_transactions' => $request->insurance_transactions,
            'zwg_insurance_transactions' => $request->zwg_insurance_transactions,
            'zinara_transactions' => $request->zinara_transactions,
            'username' => $request->username,
            'networkid' => $request->networkid,
            'siteid' => $request->siteid,
            'balance' => $request->balance,
            'code' => $request->code,
            'site_name' => $request->site_name,
            'zwg_debit_sales' => $request->zwg_debit_sales,
            'usd_debit_sales' => $request->usd_debit_sales,
            'zwg_credit_sales' => $request->zwg_credit_sales,
            'usd_credit_sales' => $request->usd_credit_sales,
            'status' => 'requested',
            'userid'=> $request->userid,
            'networkid'=> $request->networkid,
            'siteid'=> $request->siteid,
            'ammendmentdate'=> Carbon::now(),
            'ammendmentapprovaldate'=> null,
            'approverid'=> null,
        ]);
        if (!$ammendment) {
            return redirect()->back()->with('error', 'There was an error submitting the amendment request.');
        }   else{
    return redirect()->back()->with('success', 'Collection amendment saved successfully!');
   //$sql = DailyCollection::find($request->transaction_id);
     //   return view('collection.requests', compact('sql'));
        }  
    }

    public function ammendmentrequestlist()
    {
        $requests = CollectionAmmendments::where('status', 'requested')->orderBy('created_at', 'desc')->get();

        return view('collection.ammendmentrequestlist', compact('requests'));

    }

    public function viewrequest($id)
{
    $ammendment = CollectionAmmendments::findOrFail($id);
    return view('collection.viewrequest', compact('ammendment'));
}

public function calendar(Request $request)
{
    $year = $request->get('year', Carbon::now()->year);
    $month = $request->get('month', Carbon::now()->month);
    $userId = Auth::id();
    $user = Auth::user();
    
    // Create date objects for the month
    $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
    
    $siteId = $user->siteid;
    
    // Get submissions for the selected month
    // Look for records where created_at is between start and end of the month
    $submissions = DailyCollection::where('user_id', $userId)
        ->where('siteid', $siteId)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->orderBy('created_at', 'asc')
        ->get();
    
    // Debug logging
    \Log::info('Calendar Query - Month: ' . $year . '-' . $month);
    \Log::info('Date range: ' . $startDate . ' to ' . $endDate);
    \Log::info('Submissions found: ' . $submissions->count());
    
    // Build calendar data
    $calendar = [];
    foreach ($submissions as $submission) {
        $dateKey = $submission->created_at->format('Y-m-d');
        if (!isset($calendar[$dateKey])) {
            $calendar[$dateKey] = [
                'total_usd' => 0,
                'total_zwg' => 0,
            ];
        }
        $calendar[$dateKey]['total_usd'] += floatval($submission->insurance_transactions ?? 0);
        $calendar[$dateKey]['total_zwg'] += floatval($submission->zwg_insurance_transactions ?? 0);
        \Log::info('Added submission for: ' . $dateKey . ' - USD: ' . $submission->insurance_transactions);
    }
    
    // Monthly stats
    $monthlyStats = [
        'total_submissions' => $submissions->count(),
        'total_usd' => $submissions->sum('insurance_transactions'),
        'total_zwg' => $submissions->sum('zwg_insurance_transactions'),
        'unique_days' => $calendar ? count($calendar) : 0,
        'submission_days' => array_keys($calendar),
    ];
    
    return view('collection.calendar', [
        'calendar' => $calendar,
        'monthlyStats' => $monthlyStats,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'previousMonth' => $startDate->copy()->subMonth(),
        'nextMonth' => $startDate->copy()->addMonth(),
        'year' => $year,
        'month' => $month,
        'site_id' => $siteId,
        'site_name' => $user->site->site_name ?? 'Your Site',
        'sites' => collect(),
    ]);
}

// In DailyCollection.php model
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
    
    return array_values(array_diff($allDates, $existingDates));
}

public function missingSubmissions(Request $request)
{
    $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
    $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
    $siteId = $request->get('site_id');
    
    $userId = Auth::id();
    $user = User::find($userId);
    
    // Get sites based on user role
    if ($user->role->id == 3) { // Admin
        $sites = Site::orderBy('site_name')->get();
        if ($siteId) {
            $sites = $sites->where('id', $siteId);
        }
    } else {
        $sites = Site::where('id', $user->siteid)->get();
    }
    
    $missingData = [];
    
    foreach ($sites as $site) {
        $missingDates = DailyCollection::getMissingDatesForSite(
            $site->id,
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
            $user->role->id == 3 ? null : $userId
        );
        
        if (!empty($missingDates)) {
            $missingData[$site->site_name] = [
                'site_id' => $site->id,
                'site_name' => $site->site_name,
                'missing_dates' => $missingDates,
                'missing_count' => count($missingDates)
            ];
        }
    }
    
    return view('collection.missing', [
        'missingData' => $missingData,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'selectedSiteId' => $siteId,
        'sites' => $sites,
    ]);
}

public function bulkSubmitForm(Request $request)
{
    $date = $request->get('date', Carbon::now()->format('Y-m-d'));
    $siteId = $request->get('site_id');
    
    $userId = Auth::id();
    $user = User::find($userId);
    
    // Get sites based on user role
    if ($user->role->id == 3) { // Admin
        $sites = Site::orderBy('site_name')->get();
    } else {
        $sites = Site::where('id', $user->siteid)->get();
    }
    
    // If specific site is selected, filter sites
    if ($siteId) {
        $sites = $sites->where('id', $siteId);
    }
    
    // Check which sites already have submissions for this date
    $existingSubmissions = DailyCollection::whereDate('created_at', $date)
        ->when($user->role->id != 3, function($query) use ($userId) {
            return $query->where('user_id', $userId);
        })
        ->get()
        ->keyBy('siteid');
    
    return view('collection.bulk', [
        'date' => $date,
        'sites' => $sites,
        'existingSubmissions' => $existingSubmissions,
    ]);
}

public function bulkSubmitStore(Request $request)
{
    $request->validate([
        'date' => 'required|date',
        'submissions' => 'required|array',
        'submissions.*.site_id' => 'required|exists:sites,id',
        'submissions.*.usd_cash' => 'nullable|numeric|min:0',
        'submissions.*.zwg_cash' => 'nullable|numeric|min:0',
        'submissions.*.third_party_premiums' => 'nullable|numeric|min:0',
        'submissions.*.full_cover_premiums' => 'nullable|numeric|min:0',
        'submissions.*.zinara_fees' => 'nullable|numeric|min:0',
    ]);
    
    $userId = Auth::id();
    $user = User::find($userId);
    $date = Carbon::parse($request->date);
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($request->submissions as $submissionData) {
        $site = Site::find($submissionData['site_id']);
        if (!$site) continue;
        
        // Check if submission already exists for this site/date
        $existing = DailyCollection::where('siteid', $submissionData['site_id'])
            ->whereDate('created_at', $date)
            ->where(function($query) use ($user, $userId) {
                if ($user->role->id != 3) {
                    $query->where('user_id', $userId);
                }
            })
            ->first();
        
        // Calculate totals
        $thirdParty = floatval($submissionData['third_party_premiums'] ?? 0);
        $fullCover = floatval($submissionData['full_cover_premiums'] ?? 0);
        $zinaraFees = floatval($submissionData['zinara_fees'] ?? 0);
        $insuranceTransactions = $thirdParty + $fullCover + $zinaraFees;
        
        $usdCash = floatval($submissionData['usd_cash'] ?? 0);
        $zwgCash = floatval($submissionData['zwg_cash'] ?? 0);
        
        $submissionArray = [
            'siteid' => $submissionData['site_id'],
            'site_name' => $site->site_name,
            'code' => $site->code,
            'POS' => $site->POS,
            'user_id' => $userId,
            'username' => $user->name,
            'networkid' => $site->networkid,
            'created_at' => $date,
            'updated_at' => now(),
            'insurance_transactions' => $insuranceTransactions,
            'third_party_premiums' => $thirdParty,
            'full_cover_premiums' => $fullCover,
            'zinara_fees' => $zinaraFees,
            'usd_cash' => $usdCash,
            'zwg_cash' => $zwgCash,
            'usd_swipe' => floatval($submissionData['usd_swipe'] ?? 0),
            'zwg_swipe' => floatval($submissionData['zwg_swipe'] ?? 0),
            'usd_transfers' => floatval($submissionData['usd_transfers'] ?? 0),
            'zwg_transfers' => floatval($submissionData['zwg_transfers'] ?? 0),
            'usd_mpos' => floatval($submissionData['usd_mpos'] ?? 0),
            'zwg_mpos' => floatval($submissionData['zwg_mpos'] ?? 0),
            'usd_total_deposited' => floatval($submissionData['usd_total_deposited'] ?? 0),
            'zwg_total_deposited' => floatval($submissionData['zwg_total_deposited'] ?? 0),
            'bank' => $submissionData['bank'] ?? null,
            'comments' => $submissionData['comments'] ?? 'Bulk submission from calendar',
            'currency' => $submissionData['currency'] ?? 'USD_ZWG',
        ];
        
        try {
            if ($existing) {
                $existing->update($submissionArray);
            } else {
                DailyCollection::create($submissionArray);
            }
            $successCount++;
        } catch (\Exception $e) {
            $errorCount++;
        }
    }
    
    $message = "Bulk submission completed: {$successCount} successful, {$errorCount} failed.";
    
    if ($errorCount > 0) {
        return redirect()->route('daily-collections.calendar')
            ->with('warning', $message);
    }
    
    return redirect()->route('daily-collections.calendar')
        ->with('success', $message);
}
public function quickSubmit($date, $siteId)
{
    $userId = Auth::id();
    $user = User::find($userId);
    
    // Parse the date
    $submissionDate = Carbon::parse($date);
    $today = Carbon::today();
    
    // Check if the date is in the future
    if ($submissionDate->isFuture()) {
        return redirect()->route('daily-collections.calendar')
            ->with('error', 'You cannot submit collections for future dates. Please select a past or current date.');
    }
    
    // Verify the user has access to this site
    if ($user->siteid != $siteId && $user->role->id != 3) {
        return redirect()->back()->with('error', 'You do not have access to this site.');
    }
    
    // Get site info from user relationship
    $site = $user->site;
    
    if (!$site) {
        // Create a dummy site object if needed
        $site = new \stdClass();
        $site->id = $siteId;
        $site->site_name = 'Your Site';
        $site->code = null;
        $site->POS = null;
    }
    
    // Check if submission already exists for this date
    $existing = DailyCollection::where('siteid', $siteId)
        ->whereDate('created_at', $date)
        ->where('user_id', $userId)
        ->first();
    
    // If already submitted and it's not an edit request, show message
    if ($existing) {
        // Allow editing existing submissions
        return view('collection.quick', [
            'date' => $date,
            'site' => $site,
            'existing' => $existing,
            'user' => $user,
            'isCourierClerk' => false,
            'courierFaceValueBatches' => collect(),
            'isEdit' => true, // Indicate this is an edit
        ]);
    }
    
    return view('collection.quick', [
        'date' => $date,
        'site' => $site,
        'existing' => null,
        'user' => $user,
        'isCourierClerk' => false,
        'courierFaceValueBatches' => collect(),
        'isEdit' => false,
    ]);
}
public function quickSubmitStore(Request $request)
{
    $request->validate([
        'date' => 'required|date',
        'site_id' => 'required',
        'usd_cash' => 'nullable|numeric|min:0',
        'zwg_cash' => 'nullable|numeric|min:0',
        'third_party_premiums' => 'nullable|numeric|min:0',
        'full_cover_premiums' => 'nullable|numeric|min:0',
        'zinara_fees' => 'nullable|numeric|min:0',
    ]);
    
    $userId = Auth::id();
    $user = Auth::user();
    
    // The date being submitted FOR
    $submissionDate = Carbon::parse($request->date)->startOfDay();
    $today = Carbon::today();
    
    // Check if date is in the future
    if ($submissionDate->isFuture()) {
        return redirect()->back()
            ->with('error', 'Cannot submit collections for future dates.')
            ->withInput();
    }
    
    $siteId = $request->site_id;
    
    // Check if submission already exists for this specific date
    $existing = DailyCollection::where('siteid', $siteId)
        ->whereDate('created_at', $submissionDate->format('Y-m-d'))
        ->where('user_id', $userId)
        ->first();
    
    // Prevent duplicate submissions (if not editing)
    $isEdit = $request->has('is_edit') && $request->is_edit == 'true';
    
    if ($existing && !$isEdit) {
        return redirect()->back()
            ->with('error', 'A submission already exists for ' . $submissionDate->format('F j, Y') . '. You can edit it using the View/Edit button.')
            ->withInput();
    }
    
    // Get site info from the user's assigned site
    $siteName = $user->site->site_name ?? 'Site ' . $siteId;
    $siteCode = $user->site->code ?? null;
    $sitePos = $user->site->POS ?? null;
    $networkId = $user->networkid ?? $user->site->networkid ?? null;
    
    // Calculate totals (your existing calculation code here)
    $thirdParty = floatval($request->third_party_premiums ?? 0);
    $fullCover = floatval($request->full_cover_premiums ?? 0);
    $zinaraFees = floatval($request->zinara_fees ?? 0);
    $otherUsd = floatval($request->other_insurances_usd ?? 0);
    $otherZwg = floatval($request->other_insurances_zwg ?? 0);
    
    $insuranceTransactions = $thirdParty + $fullCover + $zinaraFees + $otherUsd;
    $zwgInsuranceTransactions = floatval($request->zwg_third_party_premiums ?? 0) + 
                                 floatval($request->zwg_full_cover_premiums ?? 0) + 
                                 floatval($request->zwg_zinara_fees ?? 0) + 
                                 $otherZwg;
    
    $usdCash = floatval($request->usd_cash ?? 0);
    $zwgCash = floatval($request->zwg_cash ?? 0);
    $usdSwipe = floatval($request->usd_swipe ?? 0);
    $zwgSwipe = floatval($request->zwg_swipe ?? 0);
    $usdTransfers = floatval($request->usd_transfers ?? 0);
    $zwgTransfers = floatval($request->zwg_transfers ?? 0);
    $usdMpos = floatval($request->usd_mpos ?? 0);
    $zwgMpos = floatval($request->zwg_mpos ?? 0);
    $usdTotalDeposited = floatval($request->usd_total_deposited ?? 0);
    $zwgTotalDeposited = floatval($request->zwg_total_deposited ?? 0);
    
    // Calculate debit/credit sales
    $usdTotalReceived = $usdCash + $usdSwipe + $usdTransfers + $usdMpos;
    $usdTotalPremiums = $thirdParty + $fullCover + $zinaraFees + $otherUsd;
    $usdDifference = $usdTotalReceived - $usdTotalPremiums;
    
    $usdDebitSales = $usdDifference > 0 ? $usdDifference : 0;
    $usdCreditSales = $usdDifference < 0 ? abs($usdDifference) : 0;
    
    $zwgTotalReceived = $zwgCash + $zwgSwipe + $zwgTransfers + $zwgMpos;
    $zwgTotalPremiums = floatval($request->zwg_third_party_premiums ?? 0) + 
                        floatval($request->zwg_full_cover_premiums ?? 0) + 
                        floatval($request->zwg_zinara_fees ?? 0) + 
                        $otherZwg;
    $zwgDifference = $zwgTotalReceived - $zwgTotalPremiums;
    
    $zwgDebitSales = $zwgDifference > 0 ? $zwgDifference : 0;
    $zwgCreditSales = $zwgDifference < 0 ? abs($zwgDifference) : 0;
    
    // Calculate cash in hand
    $usdCashInHand = $usdCash - $usdTotalDeposited;
    $zwgCashInHand = $zwgCash - $zwgTotalDeposited;
    
    // Prepare submission data
    $submissionData = [
        'siteid' => $siteId,
        'site_name' => $siteName,
        'code' => $siteCode,
        'POS' => $sitePos,
        'user_id' => $userId,
        'username' => $user->name,
        'networkid' => $networkId,
        'created_at' => $submissionDate,
        'updated_at' => $submissionDate,
        'insurance_transactions' => $insuranceTransactions,
        'zwg_insurance_transactions' => $zwgInsuranceTransactions,
        'third_party_premiums' => $thirdParty,
        'full_cover_premiums' => $fullCover,
        'zinara_fees' => $zinaraFees,
        'other_insurances_usd' => $otherUsd,
        'other_insurances_zwg' => $otherZwg,
        'zwg_third_party_premiums' => floatval($request->zwg_third_party_premiums ?? 0),
        'zwg_full_cover_premiums' => floatval($request->zwg_full_cover_premiums ?? 0),
        'zwg_zinara_fees' => floatval($request->zwg_zinara_fees ?? 0),
        'usd_cash' => $usdCash,
        'zwg_cash' => $zwgCash,
        'usd_swipe' => $usdSwipe,
        'zwg_swipe' => $zwgSwipe,
        'usd_transfers' => $usdTransfers,
        'zwg_transfers' => $zwgTransfers,
        'usd_mpos' => $usdMpos,
        'zwg_mpos' => $zwgMpos,
        'usd_total_deposited' => $usdTotalDeposited,
        'zwg_total_deposited' => $zwgTotalDeposited,
        'usd_cash_in_hand' => $usdCashInHand,
        'zwg_cash_in_hand' => $zwgCashInHand,
        'usd_debit_sales' => $usdDebitSales,
        'usd_credit_sales' => $usdCreditSales,
        'zwg_debit_sales' => $zwgDebitSales,
        'zwg_credit_sales' => $zwgCreditSales,
        'bank' => $request->bank,
        'comments' => $request->comments ?? 'Quick submission from calendar',
        'currency' => $request->currency ?? 'USD_ZWG',
    ];
    
    try {
        if ($existing && $isEdit) {
            // Update existing
            DB::table('daily_collections')->where('id', $existing->id)->update($submissionData);
            $message = 'Collection updated successfully for ' . $submissionDate->format('F j, Y');
        } elseif (!$existing) {
            // Insert new
            DB::table('daily_collections')->insert($submissionData);
            $message = 'Collection submitted successfully for ' . $submissionDate->format('F j, Y');
        } else {
            return redirect()->back()
                ->with('error', 'A submission already exists for this date.')
                ->withInput();
        }
        
        return redirect()->route('daily-collections.calendar', [
            'year' => $submissionDate->year,
            'month' => $submissionDate->month
        ])->with('success', $message);
        
    } catch (\Exception $e) {
        \Log::error('Quick submission error: ' . $e->getMessage());
        return redirect()->back()
            ->with('error', 'Error saving collection: ' . $e->getMessage())
            ->withInput();
    }
}
}

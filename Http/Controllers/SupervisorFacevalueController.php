<?php

namespace App\Http\Controllers;

use App\Models\FaceValue;
use Illuminate\Http\Request;
use App\Models\Supervisorfacevalues;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User; 
use App\Models\site; 
use App\Models\SBU; 
use Carbon\Carbon;

class SupervisorFacevalueController extends Controller
{
    private const COURIER_SBU = 'SBU3';
    private const GLOBAL_STOCK_ROLE_IDS = [1, 5];

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Get the SBU for the current user
     */
    private function getUserSBU()
    {
        $user = Auth::user();
        
        // Check from site
        if($user->site && $user->site->sbu) {
            return $user->site->sbu;
        }
        
        // Check from network
        if($user->network && $user->network->name) {
            return $user->network->name;
        }
        
        return null;
    }

    /**
     * Calculate ending range by adding quantity to starting range (ignoring check digit)
     * 
     * @param string $startingRange The starting serial number (e.g., "A100001" where '1' is check digit)
     * @param int $quantity The number of face values to add
     * @return string The calculated ending range
     */
    private function calculateEndingRange(string $startingRange, int $quantity): string
    {
        $serial = $this->parseFaceValueSerial($startingRange);

        if (!$serial || $quantity <= 0) {
            return $startingRange;
        }

        $endingNumeric = $this->addToNumericString($serial['numeric'], $quantity - 1);

        return $this->formatFaceValueSerial($endingNumeric, $serial['prefix'], $serial['suffix'], $serial['width']);
    }
    
    /**
     * Calculate the next starting range after allocation (ignoring check digit)
     */
    private function calculateNextStartingRange(string $startingRange, int $allocatedAmount): string
    {
        $serial = $this->parseFaceValueSerial($startingRange);

        if (!$serial || $allocatedAmount <= 0) {
            return $startingRange;
        }

        $nextNumeric = $this->addToNumericString($serial['numeric'], $allocatedAmount);

        return $this->formatFaceValueSerial($nextNumeric, $serial['prefix'], $serial['suffix'], $serial['width']);
    }

    private function parseFaceValueSerial(?string $value): ?array
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim((string) $value)));

        if ($normalized === '' || strlen($normalized) < 2) {
            return null;
        }

        $basePart = substr($normalized, 0, -1);
        $suffix = substr($normalized, -1);
        $prefix = preg_replace('/[0-9]/', '', $basePart);
        $numericPart = preg_replace('/[^0-9]/', '', $basePart);

        if ($numericPart === '') {
            return null;
        }

        return [
            'numeric' => ltrim($numericPart, '0') !== '' ? ltrim($numericPart, '0') : '0',
            'width' => strlen($numericPart),
            'prefix' => $prefix,
            'suffix' => $suffix,
        ];
    }

    private function addToNumericString(string $base, int $increment): string
    {
        $result = str_split($base !== '' ? $base : '0');
        $carry = $increment;
        $index = count($result) - 1;

        while ($index >= 0 || $carry > 0) {
            $digit = $index >= 0 ? (int) $result[$index] : 0;
            $sum = $digit + ($carry % 10);
            $carry = intdiv($carry, 10) + intdiv($sum, 10);
            $newDigit = (string) ($sum % 10);

            if ($index >= 0) {
                $result[$index] = $newDigit;
            } else {
                array_unshift($result, $newDigit);
            }

            $index--;
        }

        $value = implode('', $result);

        return ltrim($value, '0') !== '' ? ltrim($value, '0') : '0';
    }

    private function formatFaceValueSerial(string $numeric, string $prefix, string $suffix, int $width): string
    {
        return $prefix . str_pad($numeric, max($width, strlen($numeric)), '0', STR_PAD_LEFT) . $suffix;
    }

    private function isCourierSupervisor(?string $userSBU = null): bool
    {
        return $this->normalizeSbuValue($userSBU ?? $this->getUserSBU()) === self::COURIER_SBU;
    }

    private function hasGlobalStockVisibility(): bool
    {
        $roleId = (int) Auth::user()->role_id;

        if (in_array($roleId, self::GLOBAL_STOCK_ROLE_IDS, true)) {
            return true;
        }

        return $roleId === 3 && !$this->isCourierSupervisor();
    }

    private function normalizeSbuValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim($value)));

        return $normalized !== '' ? $normalized : null;
    }

    private function userMatchesSbu(User $user, ?string $userSBU): bool
    {
        $normalizedUserSbu = $this->normalizeSbuValue($userSBU);

        if (!$normalizedUserSbu) {
            return false;
        }

        $siteSbu = $this->normalizeSbuValue($user->site->sbu ?? null);
        $networkSbu = $this->normalizeSbuValue($user->network->name ?? null);

        if ($normalizedUserSbu === self::COURIER_SBU) {
            return $siteSbu === self::COURIER_SBU;
        }

        return $siteSbu === $normalizedUserSbu || $networkSbu === $normalizedUserSbu;
    }

    private function activeClerkBatchCount(int $clerkId): int
    {
        return FaceValue::where('clerk_id', $clerkId)
            ->where('is_parent', true)
            ->where('batch_balance', '>', 0)
            ->distinct('batch_id')
            ->count('batch_id');
    }
    
    /**
     * Get clerk IDs based on SBU
     */
    private function getClerksBySBU()
    {
        $userSBU = $this->getUserSBU();

        $clerks = User::whereIn('role_id', [2, 7])
            ->with(['site', 'network'])
            ->orderBy('name')
            ->get();

        if (!$this->isCourierSupervisor($userSBU)) {
            return $clerks->values();
        }

        // Courier supervisors remain restricted to SBU3 clerks only.
        $clerks = $clerks
            ->filter(fn (User $clerk) => $this->userMatchesSbu($clerk, $userSBU))
            ->values();
        
        return $clerks;
    }
    
    public function index()
    {
        $roleId = Auth::user()->role_id;
        $hasGlobalVisibility = $this->hasGlobalStockVisibility();
        $userId = Auth::id();
        
        if ($hasGlobalVisibility) {
            $supervisorfacevalueslist = Supervisorfacevalues::with('user')->where('batch_id', null)->get();
            $totalReceived = Supervisorfacevalues::sum('received');
            $totalAllocated = Supervisorfacevalues::sum('allocated');
        } else {
            $supervisorfacevalueslist = Supervisorfacevalues::with('user')->where('user_id', $userId)
                ->where('batch_id', null)
                ->get();
            $totalReceived = Supervisorfacevalues::where('user_id', $userId)->sum('received');
            $totalAllocated = Supervisorfacevalues::where('user_id', $userId)->sum('allocated');
        }
        
        $balance = $totalReceived - $totalAllocated;
        
        $supervisorfacevalues = collect();
        
        foreach($supervisorfacevalueslist as $docs) {
            $allocated = Supervisorfacevalues::where('batch_id', $docs->id)
                ->when(!$hasGlobalVisibility, function($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })
                ->get();
            
            $sumallocated = $allocated->sum('allocated');
            $supervisorfacevalues->push((object)[
                'id' => $docs->id,
                'starting' => $docs->starting,
                'ending' => $docs->ending,
                'received' => $docs->received,
                'allocated' => $sumallocated,
                'balance' => $docs->balance,
                'created_at' => $docs->created_at,
                'user_id' => $docs->user_id,
                'owner_name' => trim(($docs->user->name ?? '') . ' ' . ($docs->user->surname ?? '')) ?: 'Unknown User',
                'can_allocate' => (int) $docs->user_id === (int) $userId,
            ]);
        }
        
        return view('supervisorfacevalues.index', compact(
            'supervisorfacevalues', 
            'totalReceived', 
            'totalAllocated', 
            'balance',
            'hasGlobalVisibility'
        ));
    }
    
    public function create()
    {
        return view('supervisorfacevalues.create');
    }

    public function store(Request $request)
    {
        $starting = $request->input('starting');
        $amount = (int) $request->input('amount');
        
        // Calculate ending range using the method that ignores check digit
        $ending = $this->calculateEndingRange($starting, $amount);
        
        // Validate that ending was calculated correctly
        if ($ending === $starting) {
            return redirect()->back()
                ->with('error', 'Invalid range calculation. Please check your starting serial and quantity.')
                ->withInput();
        }
        
        $users = Supervisorfacevalues::create([
            'starting' => $starting,
            'ending' => $ending,
            'received' => $amount,
            'balance' => $amount, 
            'user_id' => Auth::id(),
            'new_starting' => $starting,
        ]);

        return redirect()->route('supervisorfacevalues.index')->with('success', 'Supervisor\'s facevalues added to stock successfully.');
    }

    public function allocate(Request $request)
    {
        $roleId = (int) Auth::user()->role_id;
        $userSBU = $this->getUserSBU();
        
        // Get allocations for this batch
        $allocations = Supervisorfacevalues::whereNotNull('assigned_to')
            ->where('batch_id', $request->id)
            ->get();
            
        $totalAllocated = $allocations->sum('allocated');
        
        // Get the batch
        $supervisorfacevalues = Supervisorfacevalues::where('id', $request->id)
            ->where('user_id', Auth::id())
            ->first();
        
        if (!$supervisorfacevalues) {
            return redirect()->route('supervisorfacevalues.index')->with('error', 'Batch not found or unauthorized.');
        }
        
        // Get clerks based on SBU (not by created_by)
        $clerks = $this->getClerksBySBU();
        
        // Debug logging
        \Log::info('===== ALLOCATE DEBUG =====');
        \Log::info('User ID: ' . Auth::id());
        \Log::info('User SBU: ' . ($userSBU ?? 'Not found'));
        \Log::info('Clerks found: ' . $clerks->count());
        
        if($clerks->isEmpty()) {
            // Check if there are any clerks in the system
            $allClerks = User::whereIn('role_id', [2, 7])->count();
            \Log::info('Total clerks in system (role 2 or 7): ' . $allClerks);
            
            if($allClerks > 0) {
                $sampleClerk = User::whereIn('role_id', [2, 7])->with(['site', 'network'])->first();
                if($sampleClerk) {
                    \Log::info('Sample clerk - Site SBU: ' . ($sampleClerk->site->sbu ?? 'No site') . ', Network: ' . ($sampleClerk->network->name ?? 'No network'));
                }
            }
        }

        $activeBatchCounts = FaceValue::whereIn('clerk_id', $clerks->pluck('id'))
            ->where('is_parent', true)
            ->where('batch_balance', '>', 0)
            ->selectRaw('clerk_id, COUNT(DISTINCT batch_id) as active_batch_count')
            ->groupBy('clerk_id')
            ->pluck('active_batch_count', 'clerk_id');

        $activeBatchIdsByClerk = FaceValue::whereIn('clerk_id', $clerks->pluck('id'))
            ->where('is_parent', true)
            ->where('batch_balance', '>', 0)
            ->get(['clerk_id', 'batch_id'])
            ->groupBy('clerk_id')
            ->map(fn ($rows) => $rows->pluck('batch_id')->unique()->values());

        return view('supervisorfacevalues.allocate', compact(
            'clerks', 
            'supervisorfacevalues', 
            'allocations', 
            'totalAllocated',
            'userSBU',
            'activeBatchCounts',
            'activeBatchIdsByClerk'
        ));
    }

    public function allocation(Request $request)
    {
        $batchnumber = $request->input('batch_id');
        $updatestock = Supervisorfacevalues::where('id', $batchnumber)->first();

        if (!$updatestock) {
            return redirect()->back()->with('error', 'Batch not found.');
        }
        
        // Verify the supervisor owns this stock
        if ($updatestock->user_id != Auth::id()) {
            return redirect()->back()->with('error', 'You are not authorized to allocate from this batch.');
        }
        
        // Only Courier supervisors stay restricted to SBU3 clerks.
        $clerkId = $request->input('clerk_id');
        $userSBU = $this->getUserSBU();
        
        if ($this->isCourierSupervisor($userSBU)) {
            $clerk = User::with(['site', 'network'])->find($clerkId);
                
            if (!$clerk || !$this->userMatchesSbu($clerk, $userSBU)) {
                return redirect()->back()->with('error', 'Courier supervisors can only allocate to SBU3 users.');
            }
        }

        $alreadyHasThisActiveBatch = FaceValue::where('clerk_id', $clerkId)
            ->where('is_parent', true)
            ->where('batch_balance', '>', 0)
            ->where('batch_id', $batchnumber)
            ->exists();

        if (!$alreadyHasThisActiveBatch && $this->activeClerkBatchCount((int) $clerkId) >= 2) {
            return redirect()->back()->with('error', 'This clerk already has two active face value batches. Allocate another batch only after one is depleted.');
        }
        
        $allocatedAmount = (int) $request->input('received');
        $stockbalance = $updatestock->balance - $allocatedAmount;
        
        // Validate allocation doesn't exceed balance
        if ($allocatedAmount > $updatestock->balance) {
            return redirect()->back()->with('error', 'Allocation amount exceeds available balance.');
        }
        
        // Calculate the ending range for this allocation (ignoring check digit)
        $startingRange = $request->input('starting');
        $endingRange = $this->calculateEndingRange($startingRange, $allocatedAmount);
        
        // Calculate the next starting range for remaining stock
        $nextStartingRange = $this->calculateNextStartingRange($startingRange, $allocatedAmount);
        
        $updatestock->update([
            'balance' => $stockbalance,
            'new_starting' => $nextStartingRange,
        ]);

        $supervisorbalance = $request->input('balance') - $allocatedAmount;
        
        Supervisorfacevalues::create([
            'starting' => $startingRange,
            'ending' => $endingRange,
            'received' => 0,
            'allocated' => $allocatedAmount, 
            'balance' => $supervisorbalance, 
            'user_id' => Auth::id(),
            'batch_id' => $batchnumber,
            'assigned_to' => $clerkId,
            'new_starting' => $endingRange,
        ]);
        
        $openingbalance = 0;
        $clerktrans = FaceValue::where('clerk_id', $clerkId)
            ->where('batch_id', $batchnumber)
            ->latest()
            ->first();
            
        if ($clerktrans) {
            $openingbalance = (float) ($clerktrans->batch_balance ?? $clerktrans->closing_balance ?? 0);
            $clerkbalance = $openingbalance + $allocatedAmount;
        } else {
            $openingbalance = 0;
            $clerkbalance = $allocatedAmount;
        }
        
        $user = User::where('id', $clerkId)->first();
        $platformName = $user ? site::whereKey($user->siteid)->value('platform_name') : null;
        
        FaceValue::create([
            'starting' => $startingRange,
            'ending' => $endingRange,
            'received' => $allocatedAmount,
            'batch_balance' => $clerkbalance,
            'used' => 0,
            'opening_balance' => $openingbalance,
            'closing_balance' => $clerkbalance,
            'clerk_id' => $clerkId,
            'is_parent' => true,
            'supervisor_id' => Auth::id(),
            'batch_id' => $batchnumber,
            'siteid' => $user->siteid ?? null,
            'networkid' => $user->networkid ?? null,
            'platform_name' => $platformName,
            'zinara_credential' => $user->zinara_credential ?? null,
            'icecash_credential' => $user->icecash_credential ?? null,
        ]);

        $bid = $batchnumber;
        return Redirect::route('allocate', ['id' => $bid])->with('success', 'Face values allocated successfully.');
    }

    public function show(Supervisorfacevalues $supervisorfacevalue)
    {
        if (Auth::user()->role_id != 5 && $supervisorfacevalue->user_id != Auth::id()) {
            abort(403, 'Unauthorized access.');
        }
        return view('supervisorfacevalues.show', compact('supervisorfacevalue'));
    }

    public function edit(Supervisorfacevalues $supervisorfacevalue)
    {
        if (Auth::user()->role_id != 5 && $supervisorfacevalue->user_id != Auth::id()) {
            abort(403, 'Unauthorized access.');
        }
        return view('supervisorfacevalues.edit', compact('supervisorfacevalue'));
    }

    public function update(Request $request, Supervisorfacevalues $supervisorfacevalue)
    {
        if (Auth::user()->role_id != 5 && $supervisorfacevalue->user_id != Auth::id()) {
            abort(403, 'Unauthorized access.');
        }
        
        $request->validate([
            'received' => 'required|numeric',
            'allocated' => 'required|numeric',
            'balance' => 'required|numeric',
        ]);

        $supervisorfacevalue->update($request->all());
        return redirect()->route('supervisorfacevalues.index')->with('success', 'supervisorfacevalue updated successfully.');
    }

    public function destroy(Supervisorfacevalues $supervisorfacevalue)
    {
        if (Auth::user()->role_id != 5 && $supervisorfacevalue->user_id != Auth::id()) {
            abort(403, 'Unauthorized access.');
        }
        
        $supervisorfacevalue->delete();
        return redirect()->route('supervisorfacevalues.index')->with('success', 'supervisorfacevalue deleted successfully.');
    }

    public function calculaterange(Request $request)
    {
        $starting = $request->input('starting');
        $amount = (int) $request->input('amount');
        
        if ($starting && $amount > 0) {
            $ending = $this->calculateEndingRange($starting, $amount);
            return response()->json(['ending' => $ending]);
        }
        
        return response()->json(['ending' => '']);
    }
}

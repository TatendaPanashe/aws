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
        if (strlen($startingRange) < 2) {
            return $startingRange;
        }
        
        // Remove the last character (check digit)
        $baseSerial = substr($startingRange, 0, -1);
        $checkDigit = substr($startingRange, -1);
        
        // Extract prefix (letters) and numeric part
        $prefix = '';
        $numericPart = '';
        
        for ($i = 0; $i < strlen($baseSerial); $i++) {
            if (is_numeric($baseSerial[$i])) {
                $numericPart = substr($baseSerial, $i);
                $prefix = substr($baseSerial, 0, $i);
                break;
            }
        }
        
        // If no prefix found, treat entire string as numeric (except check digit)
        if ($numericPart === '') {
            $numericPart = $baseSerial;
            $prefix = '';
        }
        
        // Calculate new numeric value
        $numericValue = (int) $numericPart;
        $newNumericValue = $numericValue + $quantity - 1;
        
        // Preserve the same number of digits (pad with leading zeros)
        $newNumericPadded = str_pad((string) $newNumericValue, strlen($numericPart), '0', STR_PAD_LEFT);
        
        // Build ending range (without check digit first, then add original check digit)
        $endingBase = $prefix . $newNumericPadded;
        $endingRange = $endingBase . $checkDigit;
        
        return $endingRange;
    }
    
    /**
     * Calculate the next starting range after allocation (ignoring check digit)
     */
    private function calculateNextStartingRange(string $startingRange, int $allocatedAmount): string
    {
        if (strlen($startingRange) < 2) {
            return $startingRange;
        }
        
        // Remove the last character (check digit)
        $baseSerial = substr($startingRange, 0, -1);
        $checkDigit = substr($startingRange, -1);
        
        // Extract prefix and numeric part
        $prefix = '';
        $numericPart = '';
        
        for ($i = 0; $i < strlen($baseSerial); $i++) {
            if (is_numeric($baseSerial[$i])) {
                $numericPart = substr($baseSerial, $i);
                $prefix = substr($baseSerial, 0, $i);
                break;
            }
        }
        
        if ($numericPart === '') {
            $numericPart = $baseSerial;
            $prefix = '';
        }
        
        // Calculate next starting value
        $numericValue = (int) $numericPart;
        $nextNumericValue = $numericValue + $allocatedAmount;
        
        // Preserve number of digits
        $nextNumericPadded = str_pad((string) $nextNumericValue, strlen($numericPart), '0', STR_PAD_LEFT);
        
        return $prefix . $nextNumericPadded . $checkDigit;
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

        return $siteSbu === $normalizedUserSbu || $networkSbu === $normalizedUserSbu;
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

        return view('supervisorfacevalues.allocate', compact(
            'clerks', 
            'supervisorfacevalues', 
            'allocations', 
            'totalAllocated',
            'userSBU'
        ));
    }

    public function allocation(Request $request)
    {
        $batchnumber = $request->input('batch_id');
        $updatestock = Supervisorfacevalues::where('id', $batchnumber)->first();
        
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
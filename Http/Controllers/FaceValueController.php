<?php

// app/Http/Controllers/FaceValueController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use App\Models\FaceValue;
use Carbon\Carbon;
use App\Models\User; 
use App\Models\site; 
use App\Models\SBU; 
use App\Models\Supervisorfacevalues;
use Illuminate\Support\Facades\DB;

class FaceValueController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Check if user belongs to SBU3
     */
    private function isSBU3User()
    {
        $user = Auth::user();
        
        if($user->site && $user->site->network) {
            return $user->site->network->name === 'SBU3';
        }
        
        if($user->network) {
            return $user->network->name === 'SBU3';
        }
        
        return false;
    }
    
    /**
     * Get the SBU ID for the current user based on their network
     */
    private function getUserSBU()
    {
        $user = Auth::user();
        
        if($user->site && $user->site->sbu) {
            return $user->site->sbu;
        }
        
        if($user->network && $user->network->name) {
            return $user->network->name;
        }
        
        return null;
    }

    private function getZinaraPlatform()
    {
        $user = Auth::user();
        $user->site->platform_name; 
    }
    
    /**
 * Get SBU options for face value report filters
 */
private function getFaceValueReportSbuOptions(int $roleId, ?string $resolvedSbu)
{
    $query = Site::query()
        ->whereNotNull('sbu')
        ->where('sbu', '!=', '');

    // ZINARA Supervisor (role=6) - only see their own SBU
    if ($roleId === 6 && $resolvedSbu) {
        $query->where('sbu', $resolvedSbu);
    }
    // Regular Supervisor (role=3) who is SBU3 user - only see SBU3
    elseif ($roleId === 3 && $this->isSBU3User() && $resolvedSbu) {
        $query->where('sbu', $resolvedSbu);
    }
    // Regular Supervisor (role=3) with cross-SBU access - see all SBUs
    elseif ($roleId === 3 && !$this->isSBU3User()) {
        // No additional filter - show all SBUs
    }
    // Super Admin or Admin - see all SBUs
    elseif (in_array($roleId, [1, 5], true)) {
        // No additional filter - show all SBUs
    }
    // Other roles - return empty collection
    else {
        return collect();
    }

    return $query->distinct()->orderBy('sbu')->pluck('sbu');
}
    
    /**
 * Resolve the SBU filter for face value reports based on user role and request
 */
private function resolveFaceValueReportSbu(Request $request): ?string
{
    $roleId = (int) Auth::user()->role_id;
    $userSBU = $this->getUserSBU();

    // ZINARA Supervisor (role=6) - only see their own SBU
    if ($roleId === 6) {
        return $userSBU;
    }

    // Regular Supervisor (role=3) - if they are SBU3 user, only see SBU3
    if ($roleId === 3 && $this->isSBU3User()) {
        return $userSBU;
    }

    // Regular Supervisor (role=3) with cross-SBU access - can select any SBU
    if ($roleId === 3 && !$this->isSBU3User()) {
        $selectedSbu = trim((string) $request->input('sbu'));
        return $selectedSbu !== '' ? $selectedSbu : null;
    }

    // Super Admin (role=5) or Admin (role=1) - can select any SBU
    if (in_array($roleId, [1, 5], true)) {
        $selectedSbu = trim((string) $request->input('sbu'));
        return $selectedSbu !== '' ? $selectedSbu : null;
    }

    // For clerks and other roles, return null (no SBU filter)
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
     * Calculate quantity between starting and ending ranges (ignoring check digit)
     * 
     * @param string $startingRange The starting serial number
     * @param string $endingRange The ending serial number
     * @return int The quantity of face values
     */
    private function calculateQuantityFromRange(string $startingRange, string $endingRange): int
    {
        if (strlen($startingRange) < 2 || strlen($endingRange) < 2) {
            return 0;
        }
        
        // Remove last character (check digit) from both
        $startBase = substr($startingRange, 0, -1);
        $endBase = substr($endingRange, 0, -1);
        
        // Extract numeric parts
        $startNumeric = preg_replace('/[^0-9]/', '', $startBase);
        $endNumeric = preg_replace('/[^0-9]/', '', $endBase);
        
        if ($startNumeric === '' || $endNumeric === '') {
            return 0;
        }
        
        $startValue = (int) $startNumeric;
        $endValue = (int) $endNumeric;
        
        return $endValue - $startValue + 1;
    }

    public function reportsHub()
    {
        $roleId = (int) Auth::user()->role_id;
        $isSBU3 = $this->isSBU3User();
        $userSBU = $this->getUserSBU();
        
        $stockBatches = $this->reportSupervisorStockQuery()
            ->whereNull('batch_id')
            ->get();
            
        $allocations = $this->reportSupervisorStockQuery()
            ->whereNotNull('assigned_to')
            ->get();
            
        if (($roleId === 3 || $roleId === 6) && $userSBU) {
            $authorizedClerkIds = $this->getAuthorizedClerkIdsForReport();
            if (!empty($authorizedClerkIds)) {
                $allocations = $allocations->filter(function($alloc) use ($authorizedClerkIds) {
                    return in_array($alloc->assigned_to, $authorizedClerkIds);
                });
            }
        }
        
        $activeClerkStock = $this->reportFaceValuesQuery()
            ->where('is_parent', true)
            ->select('clerk_id', DB::raw('SUM(batch_balance) as current_balance'))
            ->groupBy('clerk_id')
            ->get();
            
        $monthSpoiled = $this->reportFaceValuesQuery()
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('spoiled');
            
        $allocationByClerk = $allocations
            ->groupBy('assigned_to')
            ->map(function ($rows, $clerkId) {
                $clerk = User::find($clerkId);
                return [
                    'label' => $this->displayUserName($clerk),
                    'total' => (float) $rows->sum('allocated'),
                ];
            })
            ->sortByDesc('total')
            ->take(8)
            ->values();

        $summaryCards = [
            [
                'label' => 'Supervisor Stock Received',
                'value' => number_format($stockBatches->sum('received'), 2),
                'note' => 'Total face value stock received into supervisor inventory.',
                'icon' => 'bi bi-box-seam',
            ],
            [
                'label' => 'Allocated To Clerks',
                'value' => number_format($allocations->sum('allocated'), 2),
                'note' => 'Total face values allocated from supervisor stock.',
                'icon' => 'bi bi-arrow-left-right',
            ],
            [
                'label' => 'Supervisor Balance',
                'value' => number_format($stockBatches->sum('balance'), 2),
                'note' => 'Current supervisor-side stock balance remaining.',
                'icon' => 'bi bi-safe2',
            ],
            [
                'label' => 'Clerks With Active Stock',
                'value' => number_format($activeClerkStock->where('current_balance', '>', 0)->count()),
                'note' => 'Clerks currently holding face values in active batches.',
                'icon' => 'bi bi-people',
            ],
            [
                'label' => 'Open Batches',
                'value' => number_format($stockBatches->where('balance', '>', 0)->count()),
                'note' => 'Supervisor batches with remaining balance available for allocation.',
                'icon' => 'bi bi-upc-scan',
            ],
            [
                'label' => 'Spoiled This Month',
                'value' => number_format($monthSpoiled, 2),
                'note' => 'Face values marked as spoiled in the current month.',
                'icon' => 'bi bi-exclamation-octagon',
            ],
        ];

        $reportCards = [
            [
                'title' => 'Stock And Allocation Report',
                'description' => 'Track stock received, allocations sent to clerks, and remaining balances by batch.',
                'route' => route('facevalues.reports.stock'),
                'icon' => 'bi bi-box-seam',
                'chip' => 'Stock control',
            ],
            [
                'title' => 'Daily Clerk Entries',
                'description' => 'Review all clerk face value activity for a selected day, including ranges and spoilage.',
                'route' => route('clientfvreport'),
                'icon' => 'bi bi-calendar-day',
                'chip' => 'Daily detail',
            ],
            [
                'title' => 'Cumulative Clerk Balances',
                'description' => 'Compare opening, received, used, spoiled, and closing balances across a date range.',
                'route' => route('cumulativefvreport'),
                'icon' => 'bi bi-calendar-range',
                'chip' => 'Period summary',
            ],
            [
                'title' => 'Low Balance And Spoilage',
                'description' => 'Identify clerks running low on stock and entries with spoilage in the selected window.',
                'route' => route('facevalues.reports.exceptions'),
                'icon' => 'bi bi-clipboard2-pulse',
                'chip' => 'Exceptions',
            ],
            [
                'title' => 'Trace Face Value',
                'description' => 'Search a face value number and trace it from supervisor stock through allocation and clerk activity.',
                'route' => route('facevalues.reports.trace'),
                'icon' => 'bi bi-search',
                'chip' => 'Trace search',
            ],
        ];

        return view('facevalues.reports.hub', compact('summaryCards', 'reportCards', 'allocationByClerk'));
    }
    
    /**
 * Build face value trace for a searched number
 */
private function buildFaceValueTrace(string $searchNumber): array
{
    $serialMeta = $this->parseFaceValueSerial($searchNumber);

    if (!$serialMeta) {
        return [
            'found' => false,
            'invalid' => true,
            'message' => 'Enter a valid face value number in the same format used when the range was captured.',
        ];
    }

    $matchedOrigins = $this->reportSupervisorStockQuery()
        ->with('user')
        ->whereNull('batch_id')
        ->orderByDesc('created_at')
        ->get()
        ->filter(fn ($batch) => $this->serialInRange($serialMeta, $batch->starting, $batch->ending))
        ->values();

    if ($matchedOrigins->isEmpty()) {
        return [
            'found' => false,
            'invalid' => false,
            'message' => 'No supervisor stock batch contains the searched face value number.',
            'search' => $serialMeta['normalized'],
        ];
    }

    $originBatch = $matchedOrigins->first();
    $allocationRows = $this->reportSupervisorStockQuery()
        ->where('batch_id', $originBatch->id)
        ->whereNotNull('assigned_to')
        ->orderBy('created_at')
        ->get();

    $allocationUsers = User::with(['site', 'network'])
        ->whereIn('id', $allocationRows->pluck('assigned_to')->filter()->unique())
        ->get()
        ->keyBy('id');

    $matchedAllocation = $allocationRows->first(
        fn ($allocation) => $this->serialInRange($serialMeta, $allocation->starting, $allocation->ending)
    );

    $timeline = collect([
        [
            'date' => optional($originBatch->created_at)->format('Y-m-d H:i:s'),
            'stage' => 'Received Into Supervisor Stock',
            'range' => trim(($originBatch->starting ?? '') . ' - ' . ($originBatch->ending ?? '')),
            'quantity' => (float) $originBatch->received,
            'holder' => $this->displayUserName($originBatch->user),
            'notes' => 'Origin batch captured into supervisor stock.',
            'matched' => false,
            'tone' => 'info',
        ],
    ]);

    $traceStatus = [
        'label' => 'With Supervisor',
        'tone' => 'info',
        'description' => 'The searched face value is still inside the supervisor stock range for this batch.',
    ];
    $currentHolder = [
        'label' => $this->displayUserName($originBatch->user),
        'site' => 'Supervisor stock',
        'network' => optional(optional($originBatch->user)->network)->name ?? 'Unassigned',
    ];
    $lastMovementAt = optional($originBatch->created_at)->format('Y-m-d H:i:s');
    $inferenceNotice = null;
    $clerkActivityRows = collect();
    $currentRange = null;

    $allocationLedger = $allocationRows->map(function ($allocation) use ($matchedAllocation, $allocationUsers) {
        $clerk = $allocationUsers->get($allocation->assigned_to);

        return [
            'date' => optional($allocation->created_at)->format('Y-m-d H:i:s'),
            'range' => trim(($allocation->starting ?? '') . ' - ' . ($allocation->ending ?? '')),
            'clerk' => $this->displayUserName($clerk),
            'site' => $clerk?->site?->site_name ?? 'N/A',
            'network' => $clerk?->network?->name ?? 'Unassigned',
            'allocated' => (float) $allocation->allocated,
            'balance_after' => (float) $allocation->balance,
            'matched' => $matchedAllocation && $matchedAllocation->id === $allocation->id,
        ];
    })->values();

    if ($matchedAllocation) {
        $clerk = $allocationUsers->get($matchedAllocation->assigned_to);
        $clerkParentRecord = $this->reportFaceValuesQuery()
            ->where('is_parent', true)
            ->where('batch_id', $originBatch->id)
            ->where('clerk_id', $matchedAllocation->assigned_to)
            ->orderBy('created_at')
            ->first();

        $declarations = $this->reportFaceValuesQuery()
            ->where('is_parent', false)
            ->where('batch_id', $originBatch->id)
            ->where('clerk_id', $matchedAllocation->assigned_to)
            ->orderBy('created_at')
            ->get();

        $timeline->push([
            'date' => optional($matchedAllocation->created_at)->format('Y-m-d H:i:s'),
            'stage' => 'Allocated To Clerk',
            'range' => trim(($matchedAllocation->starting ?? '') . ' - ' . ($matchedAllocation->ending ?? '')),
            'quantity' => (float) $matchedAllocation->allocated,
            'holder' => $this->displayUserName($clerk),
            'notes' => 'Supervisor allocation covering the searched face value.',
            'matched' => true,
            'tone' => 'info',
        ]);

        $movementTrace = $this->buildClerkSerialTrace(
            $serialMeta,
            $matchedAllocation->starting,
            $matchedAllocation->ending,
            $declarations,
            $clerkParentRecord?->batch_balance
        );

        $clerkActivityRows = $movementTrace['activities'];
        $currentRange = $movementTrace['current_range'];
        $timeline = $timeline->merge($movementTrace['timeline']);
        $lastMovementAt = $movementTrace['last_movement_at']
            ?? optional($matchedAllocation->created_at)->format('Y-m-d H:i:s');
        $inferenceNotice = $movementTrace['inference_notice'];

        if ($movementTrace['status']) {
            $traceStatus = $movementTrace['status'];
        } else {
            $traceStatus = [
                'label' => 'Allocated To Clerk',
                'tone' => 'info',
                'description' => 'The searched face value sits inside a clerk allocation range, but there is no downstream declaration detail yet.',
            ];
        }

        $currentHolder = [
            'label' => $this->displayUserName($clerk),
            'site' => $clerk?->site?->site_name ?? 'N/A',
            'network' => $clerk?->network?->name ?? 'Unassigned',
        ];
    } else {
        $remainingStart = $originBatch->new_starting ?: $originBatch->starting;
        $remainingMatches = (float) $originBatch->balance > 0
            && $this->serialInRange($serialMeta, $remainingStart, $originBatch->ending);

        if ($remainingMatches) {
            $currentRange = [
                'label' => trim(($remainingStart ?? '') . ' - ' . ($originBatch->ending ?? '')),
                'quantity' => (float) $originBatch->balance,
                'date' => optional($originBatch->updated_at ?: $originBatch->created_at)->format('Y-m-d H:i:s'),
                'matched' => true,
            ];

            $timeline->push([
                'date' => optional($originBatch->updated_at ?: $originBatch->created_at)->format('Y-m-d H:i:s'),
                'stage' => 'Remaining In Supervisor Stock',
                'range' => $currentRange['label'],
                'quantity' => (float) $originBatch->balance,
                'holder' => $this->displayUserName($originBatch->user),
                'notes' => 'This face value remains available inside the supervisor stock balance.',
                'matched' => true,
                'tone' => 'success',
            ]);

            $traceStatus = [
                'label' => 'With Supervisor',
                'tone' => 'success',
                'description' => 'The searched face value has not yet been allocated out of supervisor stock.',
            ];
            $lastMovementAt = optional($originBatch->updated_at ?: $originBatch->created_at)->format('Y-m-d H:i:s');
        }
    }

    $summaryCards = [
        [
            'label' => 'Searched Number',
            'value' => $serialMeta['normalized'],
            'icon' => 'bi bi-search',
        ],
        [
            'label' => 'Current Status',
            'value' => $traceStatus['label'],
            'icon' => 'bi bi-signpost-split',
        ],
        [
            'label' => 'Current Holder',
            'value' => $currentHolder['label'],
            'icon' => 'bi bi-person-badge',
        ],
        [
            'label' => 'Origin Batch',
            'value' => '#' . $originBatch->id,
            'icon' => 'bi bi-upc-scan',
        ],
        [
            'label' => 'Last Movement',
            'value' => $lastMovementAt ?: 'Not captured',
            'icon' => 'bi bi-clock-history',
        ],
    ];

    return [
        'found' => true,
        'invalid' => false,
        'search' => $serialMeta['normalized'],
        'summary_cards' => $summaryCards,
        'status' => $traceStatus,
        'current_holder' => $currentHolder,
        'last_movement_at' => $lastMovementAt,
        'origin_batch' => [
            'id' => $originBatch->id,
            'range' => trim(($originBatch->starting ?? '') . ' - ' . ($originBatch->ending ?? '')),
            'received' => (float) $originBatch->received,
            'balance' => (float) $originBatch->balance,
            'supervisor' => $this->displayUserName($originBatch->user),
            'created_at' => optional($originBatch->created_at)->format('Y-m-d H:i:s'),
            'remaining_range' => (float) $originBatch->balance > 0
                ? trim((($originBatch->new_starting ?: $originBatch->starting) ?? '') . ' - ' . ($originBatch->ending ?? ''))
                : null,
        ],
        'matched_origins' => $matchedOrigins->map(function ($batch) {
            return [
                'batch_id' => $batch->id,
                'range' => trim(($batch->starting ?? '') . ' - ' . ($batch->ending ?? '')),
                'supervisor' => $this->displayUserName($batch->user),
                'created_at' => optional($batch->created_at)->format('Y-m-d H:i:s'),
            ];
        })->values(),
        'allocation' => $matchedAllocation ? [
            'range' => trim(($matchedAllocation->starting ?? '') . ' - ' . ($matchedAllocation->ending ?? '')),
            'allocated' => (float) $matchedAllocation->allocated,
            'created_at' => optional($matchedAllocation->created_at)->format('Y-m-d H:i:s'),
        ] : null,
        'allocation_ledger' => $allocationLedger,
        'clerk_activity_rows' => $clerkActivityRows,
        'current_range' => $currentRange,
        'timeline' => $timeline->values(),
        'inference_notice' => $inferenceNotice,
    ];
}
/**
 * Parse face value serial number
 * Extracts prefix, numeric part, suffix, and normalized value
 * 
 * @param string|null $value The serial number to parse
 * @return array|null Returns array with keys: normalized, numeric, width, prefix, suffix
 */
private function parseFaceValueSerial(?string $value): ?array
{
    $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim((string) $value)));

    if ($normalized === '' || strlen($normalized) < 2) {
        return null;
    }

    // Remove the last character (check digit) for calculation
    $basePart = substr($normalized, 0, -1);
    $suffix = substr($normalized, -1);
    
    // Extract prefix (letters) and numeric part
    $prefix = preg_replace('/[0-9]/', '', $basePart);
    $numericPart = preg_replace('/[^0-9]/', '', $basePart);

    if ($numericPart === '') {
        return null;
    }

    return [
        'normalized' => $normalized,
        'numeric' => ltrim($numericPart, '0') !== '' ? ltrim($numericPart, '0') : '0',
        'width' => strlen($numericPart),
        'prefix' => $prefix,
        'suffix' => $suffix,
    ];
}
/**
 * Check if serial is in range
 */
private function serialInRange($serial, ?string $start, ?string $end): bool
{
    $serialMeta = is_array($serial) ? $serial : $this->parseFaceValueSerial($serial);
    $startMeta = $this->parseFaceValueSerial($start);
    $endMeta = $this->parseFaceValueSerial($end);

    if (!$serialMeta || !$startMeta || !$endMeta) {
        return false;
    }

    $withinBaseRange = $this->compareNumericStrings($serialMeta['numeric'], $startMeta['numeric']) >= 0
        && $this->compareNumericStrings($serialMeta['numeric'], $endMeta['numeric']) <= 0;

    if (!$withinBaseRange) {
        return false;
    }

    $samePrefix = $serialMeta['prefix'] === $startMeta['prefix']
        && $serialMeta['prefix'] === $endMeta['prefix'];
    $sameSuffix = $serialMeta['suffix'] === $startMeta['suffix']
        && $serialMeta['suffix'] === $endMeta['suffix'];

    return $samePrefix && $sameSuffix;
}

/**
 * Compare numeric strings
 */
private function compareNumericStrings(string $left, string $right): int
{
    $normalizedLeft = ltrim($left, '0');
    $normalizedRight = ltrim($right, '0');
    $normalizedLeft = $normalizedLeft !== '' ? $normalizedLeft : '0';
    $normalizedRight = $normalizedRight !== '' ? $normalizedRight : '0';

    if (strlen($normalizedLeft) !== strlen($normalizedRight)) {
        return strlen($normalizedLeft) <=> strlen($normalizedRight);
    }

    return strcmp($normalizedLeft, $normalizedRight);
}

/**
 * Format face value serial
 */
private function formatFaceValueSerial(string $numeric, string $prefix, string $suffix, int $width): string
{
    return $prefix . str_pad($numeric, max($width, strlen($numeric)), '0', STR_PAD_LEFT) . $suffix;
}

/**
 * Add to numeric string
 */
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

    return ltrim(implode('', $result), '0') !== '' ? ltrim(implode('', $result), '0') : '0';
}

/**
 * Check if user has global face value report access
 * Super Admin (role=5) and Admin (role=1) have global access
 */
private function hasGlobalFaceValueReportAccess(int $roleId): bool
{
    return in_array($roleId, [1, 5], true);
}
    
    /**
 * Build clerk serial trace
 */
private function buildClerkSerialTrace(array $serialMeta, ?string $rangeStart, ?string $rangeEnd, $declarations, $currentBalance): array
{
    $startMeta = $this->parseFaceValueSerial($rangeStart);
    $endMeta = $this->parseFaceValueSerial($rangeEnd);

    if (!$startMeta || !$endMeta) {
        return [
            'activities' => collect(),
            'timeline' => collect(),
            'current_range' => null,
            'status' => null,
            'last_movement_at' => null,
            'inference_notice' => null,
        ];
    }

    $cursor = $startMeta['numeric'];
    $activities = collect();
    $timeline = collect();
    $status = null;
    $lastMovementAt = null;
    $currentRange = null;
    $inferenceNotice = null;

    foreach ($declarations as $declaration) {
        $usedCount = max(0, (int) round((float) $declaration->used));
        $spoiledCount = max(0, (int) round((float) $declaration->spoiled));

        if ($usedCount > 0) {
            $usedStart = $this->formatFaceValueSerial($cursor, $startMeta['prefix'], $startMeta['suffix'], $startMeta['width']);
            $usedEndNumeric = $this->addToNumericString($cursor, $usedCount - 1);
            $usedEnd = $this->formatFaceValueSerial($usedEndNumeric, $startMeta['prefix'], $startMeta['suffix'], $startMeta['width']);
            $usedMatches = $this->serialInRange($serialMeta, $usedStart, $usedEnd);

            $row = [
                'date' => optional($declaration->created_at)->format('Y-m-d H:i:s'),
                'stage' => 'Used Declaration',
                'range' => $usedStart . ' - ' . $usedEnd,
                'quantity' => $usedCount,
                'comments' => $declaration->comments ?: 'No comment',
                'matched' => $usedMatches,
                'tone' => 'warning',
            ];

            $activities->push($row);
            
            // Add to timeline with required keys
            $timeline->push([
                'date' => $row['date'],
                'stage' => $row['stage'],
                'range' => $row['range'],
                'quantity' => $row['quantity'],
                'holder' => 'Clerk declaration',
                'notes' => $row['comments'],
                'matched' => $row['matched'],
                'tone' => $row['tone'],
            ]);

            if ($usedMatches) {
                $status = [
                    'label' => 'Used',
                    'tone' => 'warning',
                    'description' => 'The searched face value falls inside a declared used range.',
                ];
                $lastMovementAt = $row['date'];
            }

            $cursor = $this->addToNumericString($cursor, $usedCount);
        }

        if ($spoiledCount > 0) {
            $spoiledStart = $this->formatFaceValueSerial($cursor, $startMeta['prefix'], $startMeta['suffix'], $startMeta['width']);
            $spoiledEndNumeric = $this->addToNumericString($cursor, $spoiledCount - 1);
            $spoiledEnd = $this->formatFaceValueSerial($spoiledEndNumeric, $startMeta['prefix'], $startMeta['suffix'], $startMeta['width']);
            $spoiledMatches = $this->serialInRange($serialMeta, $spoiledStart, $spoiledEnd);

            $row = [
                'date' => optional($declaration->created_at)->format('Y-m-d H:i:s'),
                'stage' => 'Spoiled Declaration',
                'range' => $spoiledStart . ' - ' . $spoiledEnd,
                'quantity' => $spoiledCount,
                'comments' => $declaration->comments ?: 'No comment',
                'matched' => $spoiledMatches,
                'tone' => 'danger',
            ];

            $activities->push($row);
            
            // Add to timeline with required keys
            $timeline->push([
                'date' => $row['date'],
                'stage' => $row['stage'],
                'range' => $row['range'],
                'quantity' => $row['quantity'],
                'holder' => 'Clerk declaration',
                'notes' => $row['comments'],
                'matched' => $row['matched'],
                'tone' => $row['tone'],
            ]);

            if ($spoiledMatches) {
                $status = [
                    'label' => 'Spoiled',
                    'tone' => 'danger',
                    'description' => 'The searched face value falls inside a declared spoiled range.',
                ];
                $lastMovementAt = $row['date'];
            }

            $cursor = $this->addToNumericString($cursor, $spoiledCount);
        }
    }

    $balanceCount = max(0, (int) round((float) $currentBalance));
    if ($balanceCount > 0) {
        $currentStart = $this->formatFaceValueSerial($cursor, $startMeta['prefix'], $startMeta['suffix'], $startMeta['width']);
        $currentEndNumeric = $this->addToNumericString($cursor, $balanceCount - 1);

        if ($this->compareNumericStrings($currentEndNumeric, $endMeta['numeric']) > 0) {
            $currentEndNumeric = $endMeta['numeric'];
        }

        $currentEnd = $this->formatFaceValueSerial($currentEndNumeric, $startMeta['prefix'], $startMeta['suffix'], $startMeta['width']);
        $currentMatches = $this->serialInRange($serialMeta, $currentStart, $currentEnd);

        $currentRange = [
            'label' => $currentStart . ' - ' . $currentEnd,
            'quantity' => $balanceCount,
            'date' => optional($declarations->last()?->created_at)->format('Y-m-d H:i:s'),
            'matched' => $currentMatches,
        ];

        // Add to timeline with required keys
        $timeline->push([
            'date' => $currentRange['date'] ?: 'Current',
            'stage' => 'Current Clerk Balance',
            'range' => $currentRange['label'],
            'quantity' => $currentRange['quantity'],
            'holder' => 'Clerk stock on hand',
            'notes' => 'Inferred remaining range after sequential declarations.',
            'matched' => $currentMatches,
            'tone' => 'success',
        ]);

        if ($currentMatches && !$status) {
            $status = [
                'label' => 'With Clerk',
                'tone' => 'success',
                'description' => 'The searched face value is still inside the clerk\'s remaining balance.',
            ];
            $lastMovementAt = $currentRange['date'];
        }
    }

    // Set inference notice
    $inferenceNotice = ($declarations->isNotEmpty() || $balanceCount > 0)
        ? 'Used, spoiled, and remaining serial ranges are inferred sequentially from declared counts because clerk declarations store quantities rather than exact serial numbers.'
        : null;

    return [
        'activities' => $activities->values(),
        'timeline' => $timeline->values(),
        'current_range' => $currentRange,
        'status' => $status,
        'last_movement_at' => $lastMovementAt,
        'inference_notice' => $inferenceNotice,
    ];
}
    
    public function traceReport(Request $request)
    {
        $searchNumber = trim((string) $request->input('face_value_number'));
        $traceResult = null;

        if ($searchNumber !== '') {
            $traceResult = $this->buildFaceValueTrace($searchNumber);
        }

        return view('facevalues.reports.trace', compact('searchNumber', 'traceResult'));
    }

    public function supervisorExceptionReport(Request $request)
    {
        $roleId = (int) Auth::user()->role_id;
        $resolvedSbu = $this->resolveFaceValueReportSbu($request);
        $startDate = $request->filled('startdate') ? Carbon::parse($request->startdate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->filled('enddate') ? Carbon::parse($request->enddate)->endOfDay() : Carbon::now()->endOfDay();
        $threshold = $request->filled('threshold') ? (float) $request->threshold : 20.0;

        $activeBalances = $this->reportFaceValuesQuery($resolvedSbu)
            ->where('is_parent', true)
            ->select('clerk_id', DB::raw('SUM(batch_balance) as current_balance'))
            ->groupBy('clerk_id')
            ->get()
            ->keyBy('clerk_id');

        $latestActivities = $this->reportFaceValuesQuery($resolvedSbu)
            ->select('clerk_id', DB::raw('MAX(created_at) as last_activity_at'))
            ->groupBy('clerk_id')
            ->get()
            ->keyBy('clerk_id');

        $clerkIds = collect()->merge($activeBalances->keys())->merge($latestActivities->keys())->filter()->unique();
        $clerks = User::with(['site', 'network'])
            ->whereIn('id', $clerkIds)
            ->get()
            ->keyBy('id');

        $lowBalanceRows = $activeBalances
            ->map(function ($row, $clerkId) use ($clerks, $latestActivities) {
                $clerk = $clerks->get((int) $clerkId);
                $latestActivity = $latestActivities->get($clerkId);

                return [
                    'clerk' => $this->displayUserName($clerk),
                    'site' => $clerk?->site?->site_name ?? 'N/A',
                    'network' => $clerk?->network?->name ?? 'Unassigned',
                    'current_balance' => (float) $row->current_balance,
                    'last_activity_at' => $latestActivity?->last_activity_at
                        ? Carbon::parse($latestActivity->last_activity_at)->format('Y-m-d H:i')
                        : 'No activity',
                ];
            })
            ->filter(fn ($row) => $row['current_balance'] <= $threshold)
            ->sortBy('current_balance')
            ->values();

        $spoiledRows = $this->reportFaceValuesQuery($resolvedSbu)
            ->with(['clerk.site', 'clerk.network'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('spoiled', '>', 0)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($faceValue) {
                $clerk = $faceValue->clerk;

                return [
                    'date' => optional($faceValue->created_at)->format('Y-m-d H:i:s'),
                    'clerk' => $this->displayUserName($clerk),
                    'site' => $clerk?->site?->site_name ?? 'N/A',
                    'network' => $clerk?->network?->name ?? 'Unassigned',
                    'batch_id' => $faceValue->batch_id,
                    'spoiled' => (float) $faceValue->spoiled,
                    'comments' => $faceValue->comments ?: 'No comment',
                ];
            })
            ->values();

        $summaryCards = [
            [
                'label' => 'Low Balance Clerks',
                'value' => number_format($lowBalanceRows->count()),
                'note' => 'Clerks with current batch balance at or below the selected threshold.',
                'icon' => 'bi bi-exclamation-triangle',
            ],
            [
                'label' => 'Threshold',
                'value' => number_format($threshold, 2),
                'note' => 'Low-balance limit used for the current exception report.',
                'icon' => 'bi bi-sliders',
            ],
            [
                'label' => 'Spoiled Count',
                'value' => number_format($spoiledRows->sum('spoiled'), 2),
                'note' => 'Face values marked as spoiled inside the selected reporting window.',
                'icon' => 'bi bi-exclamation-octagon',
            ],
            [
                'label' => 'Affected Clerks',
                'value' => number_format($spoiledRows->pluck('clerk')->unique()->count()),
                'note' => 'Clerks with spoilage activity in the selected reporting window.',
                'icon' => 'bi bi-people',
            ],
        ];

        $sbuOptions = $this->getFaceValueReportSbuOptions($roleId, $resolvedSbu);

        return view('facevalues.reports.exceptions', compact(
            'startDate',
            'endDate',
            'threshold',
            'lowBalanceRows',
            'spoiledRows',
            'summaryCards',
            'resolvedSbu',
            'sbuOptions'
        ));
    }
    
    /**
     * Get supervisor-created clerks (users created by this supervisor)
     */
    private function getClerkIdsCreatedBySupervisor()
    {
        $user = Auth::user();
        
        // If the supervisor created clerks directly, filter by created_by
        $clerkIds = site::where('id', 2)
            ->where('user_id', $user->id)
            ->pluck('id')
            ->toArray();
        
        return $clerkIds;
    }
    
    /**
     * Get combined clerk IDs (either created by supervisor OR from same SBU)
     */
    private function getAuthorizedClerkIds()
    {
        $roleId = (int) Auth::user()->role_id;
        
        if ($roleId !== 3) {
            return [];
        }
        
        if ($this->isSBU3User()) {
            return $this->getClerkIdsByUserSBU();
        }

        return User::whereIn('role_id', [2, 7])->pluck('id')->toArray();
    }
    
        /**
     * Scoped supervisor stock query with SBU separation
     */
    private function scopedSupervisorStockQuery()
    {
        $query = Supervisorfacevalues::query();
        $roleId = (int) Auth::user()->role_id;
        $user = Auth::user();

        if ($roleId === 3) {
            if ($this->isSBU3User()) {
                $authorizedClerkIds = $this->getAuthorizedClerkIds();
                $query->where(function($q) use ($authorizedClerkIds, $user) {
                    $q->where('user_id', $user->id)
                      ->orWhereIn('assigned_to', $authorizedClerkIds);
                });
            }
        } elseif ($roleId === 2) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Report supervisor stock query with SBU separation
     */
    private function reportSupervisorStockQuery(?string $sbu = null)
    {
        $query = Supervisorfacevalues::query();
        $roleId = (int) Auth::user()->role_id;
        $user = Auth::user();

        if ($roleId === 2) {
            $query->whereRaw('1 = 0');
        } elseif ($roleId === 3 && $this->regularSupervisorHasCrossSbuAccess($roleId)) {
            if ($sbu) {
                $authorizedClerkIds = $this->getAuthorizedClerkIdsForReport($sbu);

                $query->where(function ($stockQuery) use ($sbu, $authorizedClerkIds) {
                    $stockQuery->whereHas('user.network', function ($networkQuery) use ($sbu) {
                        $networkQuery->where('name', $sbu);
                    });
                    $stockQuery->orWhereHas('user.site', function ($siteQuery) use ($sbu) {
                        $siteQuery->where('sbu', $sbu);
                    });

                    if (!empty($authorizedClerkIds)) {
                        $stockQuery->orWhereIn('assigned_to', $authorizedClerkIds);
                    }
                });
            }
        } elseif (in_array($roleId, [3, 6], true)) {
            $authorizedClerkIds = $this->getAuthorizedClerkIdsForReport($sbu);
            
            if (!empty($authorizedClerkIds)) {
                $query->where(function($q) use ($authorizedClerkIds, $user) {
                    $q->where('user_id', $user->id)
                      ->orWhereIn('assigned_to', $authorizedClerkIds);
                });
            } else {
                $query->where('user_id', $user->id);
            }
        } elseif ($this->hasGlobalFaceValueReportAccess($roleId) && $sbu) {
            $authorizedClerkIds = $this->getAuthorizedClerkIdsForReport($sbu);

            $query->where(function ($stockQuery) use ($sbu, $authorizedClerkIds) {
                $stockQuery->whereHas('user.network', function ($networkQuery) use ($sbu) {
                    $networkQuery->where('name', $sbu);
                });
                $stockQuery->orWhereHas('user.site', function ($siteQuery) use ($sbu) {
                    $siteQuery->where('sbu', $sbu);
                });

                if (!empty($authorizedClerkIds)) {
                    $stockQuery->orWhereIn('assigned_to', $authorizedClerkIds);
                }
            });
        }

        return $query;
    }

    /**
     * Get scoped supervisor stock (alias for backward compatibility)
     */
    private function getScopedSupervisorStock()
    {
        return $this->scopedSupervisorStockQuery();
    }

    public function history(Request $request)
    {
        $clerkId = Auth::id();
        $roleId = (int) Auth::user()->role_id;
        
        // For supervisors, we need to show history for their clerks or themselves
        if ($roleId === 3) {
            $authorizedClerkIds = $this->getAuthorizedClerkIds();
            
            if ($request->has(['startdate', 'enddate'])) {
                $startdate = Carbon::parse($request->startdate);
                $enddate = Carbon::parse($request->enddate);
        
                $dates = [];
                while ($startdate->lte($enddate)) {
                    $dates[] = $startdate->toDateString();
                    $startdate->addDay();
                }
            } else {
                $dates = FaceValue::whereIn('clerk_id', $authorizedClerkIds)
                    ->selectRaw('DATE(created_at) as date')
                    ->groupBy('date')
                    ->orderBy('date', 'asc')
                    ->get(20);
            }
            
            // Rest of the history logic with authorized clerk IDs
            $docs = [];
            foreach($dates as $date){
                $dateValue = is_array($date) ? $date['date'] : $date->date;
                $yesterday = Carbon::parse($dateValue)->subDays(1)->toDateTimeString();
                
                $yesterdata = FaceValue::whereIn('clerk_id', $authorizedClerkIds)
                    ->whereDate('created_at','<=', $yesterday)
                    ->get();
                    
                $yestallused = $yesterdata->sum('used') + $yesterdata->sum('spoiled');
                $opening = $yesterdata->sum('received') - $yestallused;
                
                $data = FaceValue::whereIn('clerk_id', $authorizedClerkIds)
                    ->whereDate('created_at', $dateValue)
                    ->get();
                
                $sumreceived = $data->sum('received');
                $sumused = $data->sum('used');
                $sumspoiled = $data->sum('spoiled');
                $allused = $sumused + $sumspoiled;
                $total = $opening + $sumreceived;
                $closingBalance = $total - $allused;
                
                $docs[] = [
                    'date' => $dateValue,
                    'opening_balance' => $opening,
                    'total_spoiled' => $sumspoiled,
                    'total_used' => $sumused,
                    'total_received' => $sumreceived,
                    'closing_balance' => $closingBalance,
                ];
            }
            
            return view('facevalues.compiledhistory', compact('docs'));
        }
        
        // Original logic for clerks
        if ($request->has(['startdate', 'enddate'])) {
            $startdate = Carbon::parse($request->startdate);
            $enddate = Carbon::parse($request->enddate);
    
            $dates = [];
            while ($startdate->lte($enddate)) {
                $dates[] = $startdate->toDateString();
                $startdate->addDay();
            }
        } else {
            $dates = FaceValue::where('clerk_id', Auth::id())
                ->selectRaw('DATE(created_at) as date')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get(20);
        }

        $docs = [];
        foreach($dates as $date){
            $dateValue = is_array($date) ? $date['date'] : $date->date;
            $yesterday = Carbon::parse($dateValue)->subDays(1)->toDateTimeString();
            
            $yesterdata = FaceValue::where('clerk_id', Auth::id())
                ->whereDate('created_at','<=', $yesterday)
                ->get();
                
            $yestallused = $yesterdata->sum('used') + $yesterdata->sum('spoiled');
            $opening = $yesterdata->sum('received') - $yestallused;
            
            $data = FaceValue::where('clerk_id', Auth::id())
                ->whereDate('created_at', $dateValue)
                ->get();
            
            $sumreceived = $data->sum('received');
            $sumused = $data->sum('used');
            $sumspoiled = $data->sum('spoiled');
            $allused = $sumused + $sumspoiled;
            $total = $opening + $sumreceived;
            $closingBalance = $total - $allused;
            
            $docs[] = [
                'date' => $dateValue,
                'opening_balance' => $opening,
                'total_spoiled' => $sumspoiled,
                'total_used' => $sumused,
                'total_received' => $sumreceived,
                'closing_balance' => $closingBalance,
            ];
        }
        
        return view('facevalues.compiledhistory', compact('docs'));
    }
    
    public function clientfvreport(Request $request)
{
    $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
    $roleId = (int) Auth::user()->role_id;
    $resolvedSbu = $this->resolveFaceValueReportSbu($request);
    $authorizedClerkIds = $this->getAuthorizedClerkIdsForReport($resolvedSbu);
    
    $clerks = $this->getReportClerks($resolvedSbu);
    $selectedClerkId = $request->input('clerk_id');
    
    // Apply authorization filter
    if (!empty($authorizedClerkIds)) {
        $clerks = $clerks->whereIn('id', $authorizedClerkIds);
        
        if ($selectedClerkId && !in_array((int)$selectedClerkId, $authorizedClerkIds)) {
            $selectedClerkId = null;
        }
    }
    
    $faceValues = $this->reportFaceValuesQuery($resolvedSbu)
        ->with(['clerk.site', 'clerk.network'])
        ->whereDate('created_at', $date)
        ->when($selectedClerkId, function ($query, $selectedClerkId) {
            $query->where('clerk_id', $selectedClerkId);
        })
        ->orderByDesc('created_at')
        ->get();

    $docs = $faceValues->map(function ($faceValue) {
        $clerk = $faceValue->clerk;
        $site = $clerk?->site ?? $faceValue->site;

        return [
            'client' => $this->displayUserName($clerk),
            'siteid' => $site?->site_name ?? 'N/A',
            'network' => $clerk?->network?->name ?? 'Unassigned',
            'SBU' => $site?->sbu ?? 'No SBU',
            'date' => optional($faceValue->created_at)->format('Y-m-d H:i:s'),
            'starting_range' => $faceValue->starting,
            'ending_range' => $faceValue->ending,
            'opening_balance' => (float) $faceValue->opening_balance,
            'received' => (float) $faceValue->received,
            'used' => (float) $faceValue->used,
            'spoiled' => (float) $faceValue->spoiled,
            'closing_balance' => (float) $faceValue->closing_balance,
            'batch_balance' => (float) $faceValue->batch_balance,
            'comments' => $faceValue->comments,
            'insurance_provider' => $faceValue->insurance_provider,
            'document_channel' => $faceValue->document_channel,
            'batch_id' => $faceValue->batch_id,
            'is_parent' => (bool) $faceValue->is_parent,
        ];
    })->values();

    $summaryCards = [
        [
            'label' => 'Entries',
            'value' => number_format($docs->count()),
            'note' => 'Face value records captured on the selected day.',
            'icon' => 'bi bi-journal-text',
        ],
        [
            'label' => 'Clerks Covered',
            'value' => number_format($docs->pluck('client')->unique()->count()),
            'note' => 'Distinct clerks represented in this daily report.',
            'icon' => 'bi bi-people',
        ],
        [
            'label' => 'Received',
            'value' => number_format($docs->sum('received'), 2),
            'note' => 'Face values received on the selected date.',
            'icon' => 'bi bi-box-arrow-in-down',
        ],
        [
            'label' => 'Used',
            'value' => number_format($docs->sum('used'), 2),
            'note' => 'Face values declared as used on the selected date.',
            'icon' => 'bi bi-upc-scan',
        ],
        [
            'label' => 'Spoiled',
            'value' => number_format($docs->sum('spoiled'), 2),
            'note' => 'Face values recorded as spoiled on the selected date.',
            'icon' => 'bi bi-exclamation-octagon',
        ],
        [
            'label' => 'Closing Balance',
            'value' => number_format($docs->sum('closing_balance'), 2),
            'note' => 'Combined closing balance represented by the returned entries.',
            'icon' => 'bi bi-stack',
        ],
    ];

    $sbuOptions = $this->getFaceValueReportSbuOptions($roleId, $resolvedSbu);

    return view('facevalues.clientfvreport', compact('docs', 'date', 'clerks', 'selectedClerkId', 'summaryCards', 'resolvedSbu', 'sbuOptions'));
}

    public function cumulativefvreport(Request $request)
    {
        $roleId = (int) Auth::user()->role_id;
        $resolvedSbu = $this->resolveFaceValueReportSbu($request);
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : Carbon::now();
        $selectedClerkId = $request->input('clerk_id');
        $openingDate = $startDate->copy()->subDay()->endOfDay();

        $authorizedClerkIds = $this->getAuthorizedClerkIdsForReport($resolvedSbu);
        
        $clerks = $this->getReportClerks($resolvedSbu);
        
        if ($roleId === 3 && !empty($authorizedClerkIds)) {
            $clerks = $clerks->whereIn('id', $authorizedClerkIds);
            
            if ($selectedClerkId && !in_array($selectedClerkId, $authorizedClerkIds)) {
                $selectedClerkId = null;
            }
        }
        
        if ($selectedClerkId) {
            $clerks = $clerks->where('id', (int) $selectedClerkId)->values();
        }

        $baseQuery = $this->reportFaceValuesQuery($resolvedSbu);
        $docs = [];

        foreach ($clerks as $client) {
            // Apply authorization check for SBU3 supervisors
            if ($roleId === 3 && $this->isSBU3User() && !in_array($client->id, $authorizedClerkIds)) {
                continue;
            }
            
            $priorData = (clone $baseQuery)
                ->where('clerk_id', $client->id)
                ->whereDate('created_at', '<=', $openingDate)
                ->get();
    
            $usedBefore = $priorData->sum('used') + $priorData->sum('spoiled');
            $opening = $priorData->sum('received') - $usedBefore;
    
            $rangeData = (clone $baseQuery)
                ->where('clerk_id', $client->id)
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->get();

            if ($rangeData->isEmpty() && (float) $opening === 0.0) {
                continue;
            }
    
            $sumReceived = $rangeData->sum('received');
            $sumUsed = $rangeData->sum('used');
            $sumSpoiled = $rangeData->sum('spoiled');
    
            $totalUsed = $sumUsed + $sumSpoiled;
            $total = $opening + $sumReceived;
            $closingBalance = $total - $totalUsed;

            $site = Site::find($client->siteid);
            $sitename = $site ? $site->site_name : 'NO ERROR';
            $sbuName = $site ? $site->sbu : 'NO SBU';
            $platform = $site ? $site->platform_name : 'NO Platform';
    
            $docs[] = [
                'client' => $this->displayUserName($client),
                'site' => $sitename,
                'SBU' => $sbuName,
                'platform_name' => $platform,
                'network' => $client->network?->name ?? 'Unassigned',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'opening_balance' => $opening,
                'total_received' => $sumReceived,
                'total_used' => $sumUsed,
                'total_spoiled' => $sumSpoiled,
                'closing_balance' => $closingBalance,
            ];
        }

        $docs = collect($docs)->sortBy('client')->values();

        $summaryCards = [
            [
                'label' => 'Clerks Covered',
                'value' => number_format($docs->count()),
                'note' => 'Clerks represented in the selected date range.',
                'icon' => 'bi bi-people',
            ],
            [
                'label' => 'Opening Balance',
                'value' => number_format($docs->sum('opening_balance'), 2),
                'note' => 'Combined opening balance at the start of the report window.',
                'icon' => 'bi bi-box-arrow-right',
            ],
            [
                'label' => 'Total Received',
                'value' => number_format($docs->sum('total_received'), 2),
                'note' => 'Face values received during the selected window.',
                'icon' => 'bi bi-box-arrow-in-down',
            ],
            [
                'label' => 'Total Used',
                'value' => number_format($docs->sum('total_used'), 2),
                'note' => 'Face values declared as used during the selected window.',
                'icon' => 'bi bi-upc-scan',
            ],
            [
                'label' => 'Total Spoiled',
                'value' => number_format($docs->sum('total_spoiled'), 2),
                'note' => 'Face values declared as spoiled during the selected window.',
                'icon' => 'bi bi-exclamation-octagon',
            ],
            [
                'label' => 'Closing Balance',
                'value' => number_format($docs->sum('closing_balance'), 2),
                'note' => 'Combined closing balance at the end of the report window.',
                'icon' => 'bi bi-stack',
            ],
        ];

        $sbuOptions = $this->getFaceValueReportSbuOptions($roleId, $resolvedSbu);

        return view('facevalues.cumulativefvreport', compact(
            'docs',
            'clerks',
            'selectedClerkId',
            'startDate',
            'endDate',
            'summaryCards',
            'resolvedSbu',
            'sbuOptions'
        ));
    }

    /**
     * MODIFIED: Scoped face values query with SBU separation
     */
    private function scopedFaceValuesQuery()
{
    $query = FaceValue::query();
    $roleId = (int) Auth::user()->role_id;

    if ($roleId === 6) { // ZINARA Supervisor
        $query->where('supervisor_id', Auth::id());
    } elseif ($roleId === 7) { // ZINARA Clerk
        $query->where('clerk_id', Auth::id());
    } elseif ($roleId === 2) { // Regular Clerk
        $query->where('clerk_id', Auth::id());
    } elseif ($roleId === 3) { // Regular Supervisor
        $query->where('supervisor_id', Auth::id());
    }

    return $query;
}



   //stsrt

   /**
 * Get authorized clerk IDs based on user role
 */
/**
 * Get authorized clerk IDs based on user role for reports
 */
    private function getAuthorizedClerkIdsForReport(?string $sbu = null)
{
    $user = Auth::user();
    $roleId = (int) $user->role_id;
    $effectiveSbu = $sbu;
    
    if ($roleId == 6) { // ZINARA Supervisor
        $effectiveSbu = $this->getUserSBU();

        if (!$effectiveSbu) {
            return [];
        }

        return User::where('role_id', 7)
            ->where(function($query) use ($effectiveSbu) {
                $query->whereHas('network', function($q) use ($effectiveSbu) {
                    $q->where('name', $effectiveSbu);
                });
                $query->orWhereHas('site', function($q) use ($effectiveSbu) {
                    $q->where('sbu', $effectiveSbu);
                });
            })
            ->pluck('id')
            ->toArray();
    } elseif ($roleId == 7) { // ZINARA Clerk
        return [$user->id];
    } elseif ($roleId == 3) { // Regular Supervisor
        if ($this->isSBU3User()) {
            $effectiveSbu = $this->getUserSBU();

            if (!$effectiveSbu) {
                return [];
            }

            return User::whereIn('role_id', [2, 7])
                ->where(function($query) use ($effectiveSbu) {
                    $query->whereHas('network', function($q) use ($effectiveSbu) {
                        $q->where('name', $effectiveSbu);
                    });
                    $query->orWhereHas('site', function($q) use ($effectiveSbu) {
                        $q->where('sbu', $effectiveSbu);
                    });
                })
                ->pluck('id')
                ->toArray();
        }

        $query = User::whereIn('role_id', [2, 7]);

        if ($effectiveSbu) {
            $query->where(function ($builder) use ($effectiveSbu) {
                $builder->whereHas('network', function ($networkQuery) use ($effectiveSbu) {
                    $networkQuery->where('name', $effectiveSbu);
                });
                $builder->orWhereHas('site', function ($siteQuery) use ($effectiveSbu) {
                    $siteQuery->where('sbu', $effectiveSbu);
                });
            });
        }

        return $query->pluck('id')->toArray();
    } elseif ($roleId == 2) { // Regular Clerk
        return [$user->id];
    }

    if ($this->hasGlobalFaceValueReportAccess($roleId)) {
        $query = User::whereIn('role_id', [2, 7]);

        if ($sbu) {
            $query->where(function ($builder) use ($sbu) {
                $builder->whereHas('network', function ($networkQuery) use ($sbu) {
                    $networkQuery->where('name', $sbu);
                });
                $builder->orWhereHas('site', function ($siteQuery) use ($sbu) {
                    $siteQuery->where('sbu', $sbu);
                });
            });
        }

        return $query->pluck('id')->toArray();
    }
    
    return [];
}

/**
 * Get report clerks based on user role
 */
private function getReportClerks(?string $sbu = null)
{
    $query = User::with(['site', 'network'])
        ->whereIn('role_id', [2, 7])
        ->orderBy('name');

    $roleId = (int) Auth::user()->role_id;
    $authorizedClerkIds = $this->getAuthorizedClerkIdsForReport($sbu);

    if ($roleId == 2 || $roleId == 7) {
        $query->where('id', Auth::id());
    } elseif (in_array($roleId, [3, 6], true) || $this->hasGlobalFaceValueReportAccess($roleId)) {
        if (!empty($authorizedClerkIds)) {
            $query->whereIn('id', $authorizedClerkIds);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    return $query->get();
}

/**
 * Get report face values query with role-based filtering
 */
private function reportFaceValuesQuery(?string $sbu = null)
{
    $query = FaceValue::query();
    $roleId = (int) Auth::user()->role_id;
    $authorizedClerkIds = $this->getAuthorizedClerkIdsForReport($sbu);

    if ($roleId == 2 || $roleId == 7) {
        $query->where('clerk_id', Auth::id());
    } elseif (in_array($roleId, [3, 6], true) || $this->hasGlobalFaceValueReportAccess($roleId)) {
        if (!empty($authorizedClerkIds)) {
            $query->whereIn('clerk_id', $authorizedClerkIds);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    return $query;
}

/**
 * Display user name helper
 */
private function displayUserName($user): string
{
    if (!$user) {
        return 'Unknown User';
    }

    $fullName = trim(($user->name ?? '') . ' ' . ($user->surname ?? ''));

    return $fullName !== '' ? $fullName : 'Unknown User';
}

    /**
     * MODIFIED: Get scoped clerks based on supervisor's authorization
     */
    private function getScopedClerks()
    {
        $query = User::with(['site', 'network'])
            ->where('role_id', 2)
            ->orderBy('name');

        $roleId = (int) Auth::user()->role_id;

        if ($roleId === 3) {
            $authorizedClerkIds = $this->getAuthorizedClerkIds();
            
            if (!empty($authorizedClerkIds)) {
                $query->whereIn('id', $authorizedClerkIds);
            } else {
                $clerkIds = $this->scopedFaceValuesQuery()
                    ->select('clerk_id')
                    ->distinct()
                    ->pluck('clerk_id')
                    ->filter()
                    ->values();

                if ($clerkIds->isEmpty()) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('id', $clerkIds);
                }
            }
        } elseif ($roleId === 2) {
            $query->where('id', Auth::id());
        }

        return $query->get();
    }

    /**
     * MODIFIED: Get report clerks with SBU separation
     */
  

    // ... Keep all your existing methods (facevaluelist, declare, edit, update, etc.)
    // They will use the modified scoped queries above
    
    // Make sure to also update the supervisorStockReport method to respect SBU separation
    public function supervisorStockReport(Request $request)
    {
        $roleId = (int) Auth::user()->role_id;
        $resolvedSbu = $this->resolveFaceValueReportSbu($request);
        $startDate = $request->filled('startdate') ? Carbon::parse($request->startdate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->filled('enddate') ? Carbon::parse($request->enddate)->endOfDay() : Carbon::now()->endOfDay();

        $parentBatches = $this->reportSupervisorStockQuery($resolvedSbu)
            ->whereNull('batch_id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->get();
            
        // For SBU3 supervisors, filter allocations to only their clerks
        $allocations = $this->reportSupervisorStockQuery($resolvedSbu)
            ->whereNotNull('assigned_to')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->get();

        if ($roleId === 3 && $this->isSBU3User()) {
            $authorizedClerkIds = $this->getAuthorizedClerkIds();
            if (!empty($authorizedClerkIds)) {
                $allocations = $allocations->filter(function($alloc) use ($authorizedClerkIds) {
                    return in_array($alloc->assigned_to, $authorizedClerkIds);
                });
            }
        }

        $clerks = User::with(['site', 'network'])
            ->whereIn('id', $allocations->pluck('assigned_to')->filter()->unique())
            ->get()
            ->keyBy('id');

        // Rest of the method remains the same...
        $allParentBatches = $this->reportSupervisorStockQuery($resolvedSbu)
            ->whereNull('batch_id')
            ->get()
            ->keyBy('id');

        $allocationRows = $allocations->map(function ($allocation) use ($clerks, $allParentBatches) {
            $clerk = $clerks->get($allocation->assigned_to);
            $parentBatch = $allParentBatches->get($allocation->batch_id);

            return [
                'date' => optional($allocation->created_at)->format('Y-m-d H:i:s'),
                'batch_id' => $allocation->batch_id,
                'range' => trim(($allocation->starting ?? '') . ' - ' . ($allocation->ending ?? '')),
                'parent_range' => $parentBatch ? $parentBatch->starting . ' - ' . $parentBatch->ending : 'N/A',
                'clerk' => $this->displayUserName($clerk),
                'site' => $clerk?->site?->site_name ?? 'N/A',
                'network' => $clerk?->network?->name ?? 'Unassigned',
                'allocated' => (float) $allocation->allocated,
                'balance_after_allocation' => (float) $allocation->balance,
            ];
        })->values();

        $summaryCards = [
            [
                'label' => 'Stock Received',
                'value' => number_format($parentBatches->sum('received'), 2),
                'note' => 'Face values received into supervisor stock in the selected period.',
                'icon' => 'bi bi-box-seam',
            ],
            [
                'label' => 'Allocated',
                'value' => number_format($allocationRows->sum('allocated'), 2),
                'note' => 'Face values allocated to clerks in the selected period.',
                'icon' => 'bi bi-arrow-left-right',
            ],
            [
                'label' => 'Current Batch Balance',
                'value' => number_format($parentBatches->sum('balance'), 2),
                'note' => 'Remaining supervisor balance on the batches listed below.',
                'icon' => 'bi bi-safe2',
            ],
            [
                'label' => 'Clerks Reached',
                'value' => number_format($allocationRows->pluck('clerk')->filter()->unique()->count()),
                'note' => 'Distinct clerks who received allocations in the selected period.',
                'icon' => 'bi bi-people',
            ],
        ];

        $allocationChart = $allocationRows
            ->groupBy('clerk')
            ->map(function ($rows, $clerk) {
                return [
                    'label' => $clerk,
                    'total' => (float) $rows->sum('allocated'),
                ];
            })
            ->sortByDesc('total')
            ->take(8)
            ->values();

        $sbuOptions = $this->getFaceValueReportSbuOptions($roleId, $resolvedSbu);

        return view('facevalues.reports.stock', compact(
            'startDate',
            'endDate',
            'parentBatches',
            'allocationRows',
            'summaryCards',
            'allocationChart',
            'resolvedSbu',
            'sbuOptions'
        ));
    }

    // Keep all your existing methods unchanged below this line
    // ... (facevaluelist, declare, edit, update, recalculate, allusers, getuser, etc.)
public function facevaluelist()
{
    $roleId = (int) Auth::user()->role_id;
    
    // For ZINARA Clerk or Regular Clerk
    if ($roleId == 7 || $roleId == 2) {
        $facevaluelist = FaceValue::where('clerk_id', Auth::id())
            ->where('is_parent', true)
            ->where('batch_balance', '>', 0)
            ->orderByDesc('created_at')
            ->get();
    } else {
        // For supervisors - get their clerks' face values
        $authorizedClerkIds = $this->getAuthorizedClerkIdsForReport();
        $facevaluelist = FaceValue::whereIn('clerk_id', $authorizedClerkIds)
            ->where('is_parent', true)
            ->where('batch_balance', '>', 0)
            ->orderByDesc('created_at')
            ->get();
    }

    // Keep only the latest active parent row per batch so declarations use the live balance.
    $facevaluelist = $facevaluelist
        ->groupBy('batch_id')
        ->map(fn ($rows) => $rows->sortByDesc('created_at')->first())
        ->sortByDesc('created_at')
        ->values();
    
    $batchinfo = [];
    foreach($facevaluelist as $faceValue){
        $batch = FaceValue::where('batch_id', $faceValue->batch_id)->get();
        $batchinfo[] = [
            'received' => $batch->sum('received'),
            'used' => $batch->sum('used'),
            'closing_balance' => $batch->last()->closing_balance,
            'opening_balance' => $batch->first()->opening_balance,
            'batch_id' => $faceValue->batch_id,
            'created_at' => $faceValue->created_at,
            'date' => $faceValue->created_at->format('d-m-Y'),
        ];
    }

    $used = FaceValue::where('clerk_id', Auth::id())
        ->where(function ($query) {
            $query->where('used', '>', 0)
                ->orWhere('spoiled', '>', 0);
        })
        ->get();

    return view('facevalues.submitlist', compact('facevaluelist', 'used', 'batchinfo'));
}

public function declare(Request $request)
{
    $request->validate([
        'batch_id' => 'required',
        'fvid' => 'required|integer',
        'used' => 'required|integer|min:0',
        'spoiled' => 'required|integer|min:0',
        'insurance_provider' => 'nullable|in:Nicoz Diamond,Champions',
        'comments' => 'nullable|string',
    ]);

    $totalusednn = (int) $request->input('used') + (int) $request->input('spoiled');
    $isCourierClerk = ((int) Auth::user()->role_id === 7);

    if ($isCourierClerk && !$request->filled('insurance_provider')) {
        return redirect()->route('facevaluelist')
            ->with('error', 'Select the insurer for this Courier face value declaration before submitting.');
    }

    if ($totalusednn <= 0) {
        return redirect()->route('facevaluelist')
            ->with('error', 'Enter at least one used or spoiled face value before submitting.');
    }

    // Only declaration rows should count here, not the parent allocation row created earlier that day.
    $hasPostedToday = FaceValue::where('clerk_id', Auth::id())
        ->where('is_parent', false)
        ->whereDate('created_at', Carbon::today())
        ->where('batch_id', $request->input('batch_id'))
        ->exists();

    if ($hasPostedToday) {
        return redirect()->route('facevaluelist')
            ->with('error', 'You have already submitted a declaration for this batch today please check history and add to the balance tomorrow.');
    }

    $fvid = $request->input('fvid');
    $clerktrans = FaceValue::where('id', $fvid)
        ->where('clerk_id', Auth::id())
        ->where('is_parent', true)
        ->first();

    if (!$clerktrans) {
        return redirect()->route('facevaluelist')->with('error', 'The selected face value batch could not be found.');
    }

    $availableBalance = (int) $clerktrans->batch_balance;

    if($totalusednn > $availableBalance){
        return redirect()->route('facevaluelist')->with('error', 'Your declared face values exceed the available batch balance, please enter a correct amount and continue.');
    }

    $clerkbalance = $availableBalance - (int) $request->input('used') - (int) $request->input('spoiled');

    $clerktrans->update([
        'batch_balance' => $clerkbalance
    ]);

    FaceValue::create([
        'starting' => $request->input('starting'),
        'ending' => $request->input('ending'),
        'received' => 0,
        'used' => $request->input('used'),
        'closing_balance' => $clerkbalance,
        'opening_balance' => $availableBalance,
        'clerk_id' => Auth::id(),
        'supervisor_id' => $clerktrans->supervisor_id,
        'batch_id' => $request->input('batch_id'),
        'is_parent' => false,
        'parent_id' => $fvid,
        'spoiled' => $request->input('spoiled'),
        'comments' => $request->input('comments'),
        'insurance_provider' => $request->input('insurance_provider'),
        'document_channel' => ((int) Auth::user()->role_id === 7) ? 'Courier Connect' : 'Standard',
        'siteid' => Auth::user()->siteid,
        'networkid' => Auth::user()->networkid,
        'platform_name' => Auth::user()->site->platform_name ?? null,
    ]);
    
    return redirect()->route('facevaluelist')->with('success', 'Face value record declared successfully.');
}

public function edit(FaceValue $facevalue)
{
    return view('facevalues.edit', compact('facevalue'));
}

public function update(Request $request, FaceValue $facevalue)
{
    $request->validate([
        'received' => 'nullable|integer',
        'used' => 'nullable|integer',
        'spoiled' => 'nullable|integer',
        'comments' => 'nullable|string|max:255',
    ]);

    $facevalue->update($request->all());

    return redirect()->route('facevaluelist')->with('success', 'Face value record updated successfully.');
}

public function gethistory(Request $request)
{
    $roleId = (int) Auth::user()->role_id;
    
    if ($roleId == 7 || $roleId == 2) {
        // For clerks - show their own history
        $faceValues = FaceValue::where('clerk_id', Auth::id())->get();
    } else {
        // For supervisors - show their clerks' history
        $authorizedClerkIds = $this->getAuthorizedClerkIdsForReport();
        $faceValues = FaceValue::whereIn('clerk_id', $authorizedClerkIds)->get();
    }
    
    return view('facevalues.history', compact('faceValues'));
}

public function create()
{
    $latestRecord = FaceValue::latest()->first();
    $openingStock = $latestRecord ? $latestRecord->closing_balance : 0;
    return view('facevalues.create', compact('openingStock'));
}

public function store(Request $request)
{
    $request->validate([
        'opening_balance' => 'required|integer',
        'face_values_used' => 'required|integer',
    ]);

    $openingBalance = $request->input('opening_balance');
    $faceValuesUsed = $request->input('face_values_used');
    $closingBalance = $openingBalance - $faceValuesUsed;

    FaceValue::create([
        'opening_balance' => $openingBalance,
        'face_values_used' => $faceValuesUsed,
        'closing_balance' => $closingBalance,
        'user_id' => Auth::user()->id,
        'platform_name' => Auth::user()->site->platform_name ?? null,
    ]);
    
    return Redirect::back();
}

public function recalculate(Request $request)
{
    if ($request->filled(['thedatef', 'userid', 'thetotal_usedf'])) {
        $date = Carbon::parse($request->thedatef)->toDateString();
        
        $faceValues = FaceValue::where('clerk_id', $request->userid)
            ->whereDate('created_at', $date)
            ->first();
            
        $faceValues->used = $request->thetotal_usedf;
        
        if($faceValues->save()){
            $datesafter = FaceValue::where('clerk_id', $request->userid)
                ->whereDate('created_at', '>=', $date)
                ->get();
                
            foreach($datesafter as $dateafter){
                $previous = FaceValue::where('clerk_id', $request->userid)
                    ->whereDate('created_at', '<', $dateafter->created_at->toDateString())
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($previous) {
                    $dateafter->opening_balance = $previous->closing_balance;
                } else {
                    $dateafter->opening_balance = 0;
                }
                
                $allused = $dateafter->used + $dateafter->spoiled;
                $total = $previous->closing_balance + $dateafter->received;
                $closingBalance = $total - $allused;
                $dateafter->closing_balance = $closingBalance;
                $dateafter->save();
            }

            return redirect()->back()->with('success', 'Recalculation Complete.');
        }
    } else {
        return redirect()->back()->with('error', 'Missing required values in request.');
    }
    
    return view('facevalues.history', compact('faceValues'));
}

public function allusers()
{
    $users = User::all();
    return view('fvrecalc.allusers', compact('users'));
}

public function getuser(Request $request, $userid)
{
    $today = Carbon::today();
    $hundredDaysAgo = Carbon::today()->subDays(100);
    
    if ($request->has(['startdate', 'enddate'])) {
        $startdate = Carbon::parse($today);
        $enddate = Carbon::parse($hundredDaysAgo);
        $dates = [];
        while ($startdate->lte($enddate)) {
            $dates[] = $startdate->toDateString();
            $startdate->addDay();
        }
    } else {
        $dates = FaceValue::where('clerk_id', $userid)
            ->selectRaw('DATE(created_at) as date')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get(100);
    }

    $docs = [];
    foreach($dates as $date){
        $yesterday = Carbon::parse($date->date)->subDays(1)->toDateTimeString();
        $yesterdata = FaceValue::where('clerk_id', $userid)
            ->whereDate('created_at','<=', $yesterday)
            ->get();
        $yestallused = $yesterdata->sum('used') + $yesterdata->sum('spoiled');
        $opening = $yesterdata->sum('received') - $yestallused;

        $data = FaceValue::where('clerk_id', $userid)
            ->whereDate('created_at', $date->date)
            ->get();
      
        $sumreceived = $data->sum('received');
        $sumused = $data->sum('used');
        $sumspoiled = $data->sum('spoiled');
        $allused = $sumused + $sumspoiled;
        $total = $opening + $sumreceived;
        $closingBalance = $total - $allused;
        
        $docs[] = [
            'date' => $date->date,
            'opening_balance' => $opening,
            'total_spoiled' => $sumspoiled,
            'total_used' => $sumused,
            'total_received' => $sumreceived,
            'closing_balance' => $closingBalance,
        ];
    }
    
    $user = User::find($userid);
    return view('fvrecalc.userhistory', compact('docs','user'));
}

public function compiledhistory(Request $request)
{
    if ($request->has(['startdate', 'enddate'])) {
        $startdate = Carbon::parse($request->startdate);
        $enddate = Carbon::parse($request->enddate);
        $dates = [];
        while ($startdate->lte($enddate)) {
            $dates[] = $startdate->toDateString();
            $startdate->addDay();
        }
    } else {
        $dates = FaceValue::where('clerk_id', Auth::id())
            ->selectRaw('DATE(created_at) as date')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get(20);
    }

    $docs = [];
    foreach($dates as $date){
        $yesterday = Carbon::parse($date->date)->subDays(1)->toDateTimeString();
        $yesterdata = FaceValue::where('clerk_id', Auth::id())
            ->whereDate('created_at','<=', $yesterday)
            ->get();
        $yestallused = $yesterdata->sum('used') + $yesterdata->sum('spoiled');
        $opening = $yesterdata->sum('received') - $yestallused;

        $data = FaceValue::where('clerk_id', Auth::id())
            ->whereDate('created_at', $date->date)
            ->get();
      
        $sumreceived = $data->sum('received');
        $sumused = $data->sum('used');
        $sumspoiled = $data->sum('spoiled');
        $allused = $sumused + $sumspoiled;
        $total = $opening + $sumreceived;
        $closingBalance = $total - $allused;
        
        $docs[] = [
            'date' => $date->date,
            'opening_balance' => $opening,
            'total_spoiled' => $sumspoiled,
            'total_used' => $sumused,
            'total_received' => $sumreceived,
            'closing_balance' => $closingBalance,
        ];
    }
    
    return view('facevalues.compiledhistory', compact('docs'));
}

public function detailedhistory(Request $request)
{
    $facevaluelist = FaceValue::where('clerk_id', Auth::id())
        ->where('is_parent', true)
        ->orderBy('created_at', 'asc')
        ->get(10);

    return view('facevalues.detailedhistory', compact('facevaluelist'));
}
   
}

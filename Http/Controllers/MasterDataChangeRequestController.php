<?php

namespace App\Http\Controllers;

use App\Models\MasterDataChangeRequest;
use App\Models\Network;
use App\Models\site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterDataChangeRequestController extends Controller
{
    private const SUPER_USER_ROLE_ID = 5;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = MasterDataChangeRequest::with(['requester', 'reviewer'])->orderByDesc('created_at');

        if ((int) $user->role_id !== self::SUPER_USER_ROLE_ID) {
            $query->where('requested_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->get();

        return view('change-requests.index', [
            'requests' => $requests,
            'isSuperUser' => (int) $user->role_id === self::SUPER_USER_ROLE_ID,
        ]);
    }

    public function show(MasterDataChangeRequest $masterDataChangeRequest)
    {
        $user = Auth::user();

        if ((int) $user->role_id !== self::SUPER_USER_ROLE_ID && (int) $masterDataChangeRequest->requested_by !== (int) $user->id) {
            abort(403, 'Unauthorized access.');
        }

        return view('change-requests.show', [
            'changeRequest' => $masterDataChangeRequest->load(['requester', 'reviewer']),
            'isSuperUser' => (int) $user->role_id === self::SUPER_USER_ROLE_ID,
            'fieldLabels' => $this->fieldLabels($masterDataChangeRequest->target_type),
        ]);
    }

    public function approve(Request $request, MasterDataChangeRequest $masterDataChangeRequest)
    {
        $this->ensureSuperUser();

        if ($masterDataChangeRequest->status !== 'pending') {
            return redirect()->route('master-data-change-requests.show', $masterDataChangeRequest)
                ->with('error', 'This request has already been reviewed.');
        }

        try {
            DB::transaction(function () use ($request, $masterDataChangeRequest) {
                $target = $this->findTarget($masterDataChangeRequest);

                if ($masterDataChangeRequest->action === 'delete') {
                    $target->delete();
                } else {
                    $target->update($masterDataChangeRequest->requested_data ?: []);
                }

                $masterDataChangeRequest->update([
                    'status' => 'approved',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'reviewer_notes' => $request->input('reviewer_notes'),
                ]);
            });
        } catch (\Throwable $exception) {
            return back()->with('error', 'Unable to approve request: ' . $exception->getMessage());
        }

        return redirect()->route('master-data-change-requests.index')
            ->with('success', 'Change request approved and applied successfully.');
    }

    public function reject(Request $request, MasterDataChangeRequest $masterDataChangeRequest)
    {
        $this->ensureSuperUser();

        if ($masterDataChangeRequest->status !== 'pending') {
            return redirect()->route('master-data-change-requests.show', $masterDataChangeRequest)
                ->with('error', 'This request has already been reviewed.');
        }

        $masterDataChangeRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'reviewer_notes' => $request->input('reviewer_notes'),
        ]);

        return redirect()->route('master-data-change-requests.index')
            ->with('success', 'Change request rejected.');
    }

    private function ensureSuperUser(): void
    {
        if ((int) Auth::user()->role_id !== self::SUPER_USER_ROLE_ID) {
            abort(403, 'Only super users can approve or reject master data changes.');
        }
    }

    private function findTarget(MasterDataChangeRequest $changeRequest)
    {
        if ($changeRequest->target_type === 'site') {
            return site::findOrFail($changeRequest->target_id);
        }

        if ($changeRequest->target_type === 'network') {
            return Network::findOrFail($changeRequest->target_id);
        }

        throw new \InvalidArgumentException('Unsupported change request target.');
    }

    private function fieldLabels(string $targetType): array
    {
        if ($targetType === 'site') {
            return [
                'site_name' => 'Site Name',
                'network_id' => 'Network ID',
                'site_description' => 'Site Description',
                'code_name' => 'Code Name',
                'code' => 'Site Code',
                'POS' => 'POS ID',
                'bank' => 'Bank',
                'sbu' => 'SBU',
                'platform_name' => 'Platform',
            ];
        }

        return [
            'name' => 'Network Name',
            'city' => 'City',
            'description' => 'Description',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\site;
use App\Models\Network;
use App\Models\MasterDataChangeRequest;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class SiteController extends Controller
{
    private const APPROVAL_REQUIRED_ROLE_IDS = [1, 3, 6];
    private const COURIER_PLATFORM = 'Courier Connect';

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function courierSitesQuery()
    {
        return site::whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)]);
    }

    private function isCourierConnectSite(site $site): bool
    {
        return strtoupper(trim((string) $site->platform_name)) === strtoupper(self::COURIER_PLATFORM);
    }

    public function index()
    {
        $user = Auth::user();
       
        
        $roleId = (int) $user->role_id;
        $isZINARASupervisor = ($roleId == 6);
        $isZINARAClerk = ($roleId == 7);
        $isZIMPOSTViewer = ($roleId == 8);

        $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk || $isZIMPOSTViewer);

        
        if ($isZINARAUser) {
            $sites = $this->courierSitesQuery()->with('network', 'user')->get();
        } else {
            // Regular users see all office nodes
            $sites = site::with('network', 'user','platform') 
            ->get();
        }
        
        return view('site.index', compact('sites', 'isZINARASupervisor', 'isZINARAUser', 'isZINARAClerk'));
    }

    public function create()
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $isZINARASupervisor = ($roleId == 6);
        $isZINARAClerk = ($roleId == 7);
        $isZIMPOSTViewer = ($roleId == 8);
        $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);

        if ($isZIMPOSTViewer) {
            return redirect()->route('sites')->with('error', 'You do not have permission to create sites.');
        }

        $networks = Network::all();

        $platforms = Platform::all();

        return view('site.create', compact('networks', 'platforms', 'isZINARAUser'));
    }

    public function store(Request $request)
    {
        $platforms = Platform::all();
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $isZINARASupervisor = ($roleId == 6);
        $isZINARAClerk = ($roleId == 7);
        $isZIMPOSTViewer = ($roleId == 8);
        $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
        
        if ($isZIMPOSTViewer) {
            return redirect()->route('sites')->with('error', 'You do not have permission to create or edit sites.');
        }

        if ($isZINARAUser) {
            $request->merge(['platform_name' => self::COURIER_PLATFORM]);
        }
        
        $rules = [
            'site_name' => 'required|string|max:255',
            'network_id' => 'required|exists:network,id',
            'site_description' => 'nullable|string',
            'code_name' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:50',
            'POS' => 'nullable|string|max:50',
            'bank' => 'nullable|string|max:100',
            "platform_name" => 'nullable|string', // Assuming you have a platform_id field in your form
        ];

        
        
        $request->validate($rules);
        
        site::create([
            'site_name' => $request->site_name,
            'network_id' => $request->network_id,
            'site_description' => $request->site_description,
            'code_name' => $request->code_name,
            'code' => $request->code,
            'POS' => $request->POS,
            'bank' => $request->bank,
            'user_id' => Auth::id(),
            'platform_name' => $request->platform_name, // Assuming you have a platform_id field in your form
        ]);
       //dd($request);
        
        return Redirect::route('sites')->with('success', 'Office node created successfully.');
    }

    public function show($id)
    {
        $site = site::findOrFail($id);
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $isZINARAUser = in_array($roleId, [6, 7, 8]);
        
        if ($isZINARAUser && !$this->isCourierConnectSite($site)) {
            abort(403, 'Unauthorized access.');
        }
        
        // Get ZINARA clerks for this site if user is ZINARA Supervisor
        $zinaraClerks = [];
        if ($roleId == 6) {
            $zinaraClerks = User::where('siteid', $site->id)
                ->where('role_id', 7)
                ->where('created_by', Auth::id())
                ->get();
        }
        
        return view('site.show', compact('site', 'zinaraClerks'));
    }

    public function edit($id)
    {
        $site = site::findOrFail($id);
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $isZIMPOSTViewer = ($roleId == 8);
        $isZINARAUser = in_array($roleId, [6, 7]);

        if ($isZIMPOSTViewer) {
            abort(403, 'Unauthorized access.');
        }

        if ($isZINARAUser && !$this->isCourierConnectSite($site)) {
            abort(403, 'Unauthorized access.');
        }

        $networks = Network::all();

        $platforms = Platform::all();

        return view('site.edit', compact('site', 'networks', 'platforms', 'isZINARAUser'));
    }

    public function update(Request $request)
    {
        $id = $request->id;
        $site = site::findOrFail($id);

        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $isZIMPOSTViewer = ($roleId == 8);
        $isZINARAUser = in_array($roleId, [6, 7]);

        if ($isZIMPOSTViewer) {
            abort(403, 'Unauthorized access.');
        }

        if ($isZINARAUser && !$this->isCourierConnectSite($site)) {
            return redirect()->back()->with('error', 'Unauthorized access.')->withInput();
        }

        if (!$request->has('network_id')) {
            return redirect()->back()->with('error', 'Network ID is required')->withInput();
        }

        $rules = [
            'site_name' => 'required|string|max:255',
            'network_id' => 'required|exists:network,id',
            'site_description' => 'nullable|string',
            'code_name' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:50',
            'POS' => 'nullable|string|max:50',
            'bank' => 'nullable|string|max:100',
            'platform_name' => 'nullable|string|max:50',
        ];

        if ($isZINARAUser) {
            $request->merge(['platform_name' => self::COURIER_PLATFORM]);
        }

        $validated = $request->validate($rules);

        $update = [
            'site_name' => $validated['site_name'],
            'network_id' => $validated['network_id'],
            'site_description' => $validated['site_description'] ?? null,
            'code_name' => $validated['code_name'] ?? null,
            'code' => $validated['code'] ?? null,
            'POS' => $validated['POS'] ?? null,
            'bank' => $validated['bank'] ?? null,
            'platform_name' => $validated['platform_name'] ?? null,
        ];

        if ($this->requiresApproval($user)) {
            $changeRequest = $this->createSiteChangeRequest($site, 'update', $update);

            if (!$changeRequest) {
                return redirect()->back()->with('error', 'No office changes were detected.')->withInput();
            }

            return Redirect::route('sites')
                ->with('success', 'Office update request submitted for super user approval.');
        }

        $site->update($update);
        return Redirect::route('sites')->with('success', 'Office updated successfully.');
    }

    public function destroy(Request $request)
    {
        $site = site::findOrFail($request->id);
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $isZIMPOSTViewer = ($roleId == 8);
        $isZINARAUser = in_array($roleId, [6, 7]);
        
        if ($isZIMPOSTViewer) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        if ($isZINARAUser && !$this->isCourierConnectSite($site)) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }
        
        // Check if there are clerks assigned to this office
        $assignedClerks = User::where('siteid', $site->id)->where('role_id', 7)->count();
        if ($assignedClerks > 0 && $isZINARAUser) {
            return redirect()->back()->with('error', 'Cannot delete office with assigned clerks. Reassign or delete clerks first.');
        }
        
        if ($this->requiresApproval($user)) {
            $changeRequest = $this->createSiteChangeRequest($site, 'delete', []);

            if (!$changeRequest) {
                return redirect()->back()->with('error', 'A pending delete request already exists for this site.');
            }

            return Redirect::route('sites')
                ->with('success', 'Office delete request submitted for super user approval.');
        }

        $site->delete();
        
        return Redirect::route('sites')->with('success', 'Office deleted successfully.');
    }

    private function requiresApproval(User $user): bool
    {
        return in_array((int) $user->role_id, self::APPROVAL_REQUIRED_ROLE_IDS, true);
    }

    private function createSiteChangeRequest(site $site, string $action, array $requestedData): ?MasterDataChangeRequest
    {
        $fields = [
            'site_name',
            'network_id',
            'site_description',
            'code_name',
            'code',
            'POS',
            'bank',
            'platform_name',
        ];

        $originalData = $site->only($fields);

        if ($action === 'update') {
            $requestedData = array_intersect_key($requestedData, array_flip($fields));

            $hasChanges = collect($requestedData)->contains(function ($value, $field) use ($originalData) {
                return (string) ($originalData[$field] ?? '') !== (string) ($value ?? '');
            });

            if (!$hasChanges) {
                return null;
            }
        }

        $pendingRequest = MasterDataChangeRequest::where('target_type', 'site')
            ->where('target_id', $site->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            return null;
        }

        return MasterDataChangeRequest::create([
            'target_type' => 'site',
            'target_id' => $site->id,
            'action' => $action,
            'original_data' => $originalData,
            'requested_data' => $action === 'delete' ? [] : $requestedData,
            'status' => 'pending',
            'requested_by' => Auth::id(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterDataChangeRequest;
use App\Models\Network;
use Illuminate\Support\Facades\Auth;

class NetworkController extends Controller
{
    private const APPROVAL_REQUIRED_ROLE_IDS = [1, 3, 6];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the network resources.
     */
    public function index()
    {
        $networks = Network::with('user')->get();
        return view('networks.index', compact('networks'));
    }

    /**
     * Show the form for creating a new network.
     */
    public function create()
    {
        return view('networks.create');
    }

    /**
     * Store a newly created network in the database.
     */
    public function store(Request $request)
    {
        

        $sql = Network::create([

            'name' => $request->input('name'),
            'city'=> $request->input('city'),
           // 'province' => $request->input('province'),
            'description' => $request->input('description'),
            
            'user_id' => Auth::user()->id,
            
           
        ]);

        return redirect()->route('networks.index')
                        ->with('success', 'Network created successfully.');
    }

    /**
     * Display the specified network.
     */
    public function show(Network $network)
    {
        return view('networks.show', compact('network'));
    }

    /**
     * Show the form for editing the specified network.
     */
    public function edit(Network $network)
    {
        return view('networks.edit', compact('network'));
    }

    /**
     * Update the specified network in the database.
     */
    public function update(Request $request, Network $network)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if ($this->requiresApproval(Auth::user())) {
            $changeRequest = $this->createNetworkChangeRequest($network, 'update', $validated);

            if (!$changeRequest) {
                return redirect()->back()->with('error', 'No network changes were detected or a pending request already exists.')->withInput();
            }

            return redirect()->route('networks.index')
                ->with('success', 'Network update request submitted for super user approval.');
        }

        $network->update($validated);

        return redirect()->route('networks.index')
                        ->with('success', 'Network updated successfully.');
    }

    /**
     * Remove the specified network from the database.
     */
    public function destroy(Request $request)
    {
        $network = Network::findOrFail($request->input('id'));

        if ($this->requiresApproval(Auth::user())) {
            $changeRequest = $this->createNetworkChangeRequest($network, 'delete', []);

            if (!$changeRequest) {
                return redirect()->back()->with('error', 'A pending delete request already exists for this network.');
            }

            return redirect()->route('networks.index')
                ->with('success', 'Network delete request submitted for super user approval.');
        }

        $network->delete();

        return redirect()->route('networks.index')
                        ->with('success', 'Network deleted successfully.');
    }

    private function requiresApproval($user): bool
    {
        return $user && in_array((int) $user->role_id, self::APPROVAL_REQUIRED_ROLE_IDS, true);
    }

    private function createNetworkChangeRequest(Network $network, string $action, array $requestedData): ?MasterDataChangeRequest
    {
        $fields = ['name', 'city', 'description'];
        $originalData = $network->only($fields);

        if ($action === 'update') {
            $requestedData = array_intersect_key($requestedData, array_flip($fields));

            $hasChanges = collect($requestedData)->contains(function ($value, $field) use ($originalData) {
                return (string) ($originalData[$field] ?? '') !== (string) ($value ?? '');
            });

            if (!$hasChanges) {
                return null;
            }
        }

        $pendingRequest = MasterDataChangeRequest::where('target_type', 'network')
            ->where('target_id', $network->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            return null;
        }

        return MasterDataChangeRequest::create([
            'target_type' => 'network',
            'target_id' => $network->id,
            'action' => $action,
            'original_data' => $originalData,
            'requested_data' => $action === 'delete' ? [] : $requestedData,
            'status' => 'pending',
            'requested_by' => Auth::id(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\site;
use App\Models\Network;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class SiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
            // ZINARA users and ZIMPOST Viewer can only see SBU3 sites (Courier)
            $sites = site::where('sbu', 'SBU3')->with('network', 'user')->get();
        } else {
            // Regular users see all sites
            $sites = site::with('network', 'user','platform') 
            ->get();
        }
        
        // If ZINARA Supervisor, eager load the clerks for each site
        if ($isZINARASupervisor) {
            foreach ($sites as $site) {
                $site->zinaraClerks = User::where('siteid', $site->id)
                    ->where('role_id', 7)
                    ->where('created_by', Auth::id())
                    ->get();
            }
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

        if ($isZINARAUser) {
            $sbu3Network = Network::where('name', 'SBU3')->first();

            if (!$sbu3Network) {
                return redirect()->route('sites')
                    ->with('error', 'SBU3 network not found. Please contact administrator.');
            }

            $networks = Network::where('name', 'SBU3')->get();
        } else {
            $networks = Network::all();
        }

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

        // For ZINARA users, automatically set network_id to SBU3 network
        if ($isZINARAUser) {
            $sbu3Network = Network::where('name', 'SBU3')->first();
            
            if (!$sbu3Network) {
                return redirect()->back()
                    ->with('error', 'SBU3 network not found. Please contact administrator to create the Courier network first.')
                    ->withInput();
            }
            
            $request->merge(['network_id' => $sbu3Network->id]);
            $request->merge(['sbu' => 'SBU3']);
        }
        
        $rules = [
            'site_name' => 'required|string|max:255',
            'network_id' => 'required|exists:network,id',
            'site_description' => 'nullable|string',
            'code_name' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:50',
            'POS' => 'nullable|string|max:50',
            'bank' => 'nullable|string|max:100',
            'sbu' => 'required|in:SBU1,SBU2,SBU3',
            "platform_name" => 'nullable|string', // Assuming you have a platform_id field in your form
        ];

        
        
        // ZINARA users can only create SBU3 sites
        if ($isZINARAUser && $request->sbu != 'SBU3') {
            return redirect()->back()
                ->with('error', 'ZINARA users can only create sites under SBU3 (Courier).')
                ->withInput();
        }
        
        $request->validate($rules);
        
        site::create([
            'site_name' => $request->site_name,
            'network_id' => $request->network_id,
            'site_description' => $request->site_description,
            'code_name' => $request->code_name,
            'code' => $request->code,
            'POS' => $request->POS,
            'bank' => $request->bank,
            'sbu' => $request->sbu,
            'user_id' => Auth::id(),
            'platform_name' => $request->platform_name, // Assuming you have a platform_id field in your form
        ]);
       //dd($request);
        
        return Redirect::route('sites')->with('success', 'Site created successfully.');
    }

    public function show($id)
    {
        $site = site::findOrFail($id);
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $isZINARAUser = in_array($roleId, [6, 7, 8]);
        
        // ZINARA users and ZIMPOST Viewer can only view SBU3 sites
        if ($isZINARAUser && $site->sbu != 'SBU3') {
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

        if ($isZINARAUser) {
            $sbu3Network = Network::where('name', 'SBU3')->first();
            if (!$sbu3Network) {
                return redirect()->route('sites')
                    ->with('error', 'SBU3 network not found. Please contact administrator.');
            }
            $networks = Network::where('name', 'SBU3')->get();
        } else {
            $networks = Network::all();
        }

        $platforms = Platform::all();

        return view('site.edit', compact('site', 'networks', 'platforms'));
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

        if ($isZINARAUser && $site->sbu !== 'SBU3') {
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

        if (!$isZINARAUser && $request->has('sbu')) {
            $rules['sbu'] = 'required|in:SBU1,SBU2,SBU3';
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

        $site->update($update);
        return Redirect::route('sites')->with('success', 'Site updated successfully.');
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

        // ZINARA users can only delete SBU3 sites
        if ($isZINARAUser && $site->sbu != 'SBU3') {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }
        
        // Check if there are clerks assigned to this site
        $assignedClerks = User::where('siteid', $site->id)->where('role_id', 7)->count();
        if ($assignedClerks > 0 && $isZINARAUser) {
            return redirect()->back()->with('error', 'Cannot delete site with assigned ZINARA clerks. Reassign or delete clerks first.');
        }
        
        $site->delete();
        
        return Redirect::route('sites')->with('success', 'Site deleted successfully.');
    }
}
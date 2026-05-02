<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\site;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserTeam;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;

class TeamsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function resetpwd($id)
    {
        $user = User::findOrFail($id);
        
        // Check authorization
        $currentUser = Auth::user();
        $roleId = (int) $currentUser->role_id;
        
        if ($roleId == 6 && ($user->role_id != 7 || $user->created_by != $currentUser->id)) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }
        
        $user->password = Hash::make('Test123!');
        $user->save();
        
        return Redirect::route('teams.index')
            ->with('success', 'Password reset successfully.');
    }

  protected function index()
{
    $user = Auth::user();
    $roleId = (int) $user->role_id;
    $isZINARASupervisor = ($roleId == 6);
    $isZINARAClerk = ($roleId == 7);
    
    // Debug: Log the current user and role
    \Log::info('===== TEAMS INDEX DEBUG =====');
    \Log::info('Current User ID: ' . $user->id);
    \Log::info('Current User Role ID: ' . $roleId);
    
    if ($isZINARASupervisor) {
        // ZINARA Supervisor - only see ZINARA Clerks they created
        $users = User::with(['role', 'site', 'network'])
            ->where('role_id', 7)
            ->where('created_by', $user->id)
            ->orderBy('name')
            ->get();
        
        \Log::info('ZINARA Supervisor - Users found: ' . $users->count());
        
        // If no users found, show all ZINARA clerks for debugging (temporary)
        if($users->isEmpty()) {
            \Log::warning('No ZINARA clerks found with created_by=' . $user->id);
            $users = User::with(['role', 'site', 'network'])
                ->where('role_id', 7)
                ->get();
            \Log::info('All ZINARA clerks in system: ' . $users->count());
        }
    } 
    elseif ($isZINARAClerk) {
        // ZINARA Clerk - only see themselves
        $users = User::with(['role', 'site', 'network'])
            ->where('id', $user->id)
            ->get();
        \Log::info('ZINARA Clerk - Users found: ' . $users->count());
    } 
    else {
        // Regular Supervisors (role_id = 3), Admins (role_id = 1), 
        // Super Users (role_id = 5), Managers (role_id = 4)
        // Can see ALL users
        $users = User::with(['role', 'site', 'network'])
            ->orderBy('name')
            ->get();
        
        \Log::info('Regular/Admin/Super User - All users count: ' . $users->count());
    }
    
    \Log::info('===== END DEBUG =====');
    
    return view('teams.index', compact('users'));
}

    protected function create(Request $request)
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $isZINARASupervisor = ($roleId == 6);
        $isZINARAClerk = ($roleId == 7);
        $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
        
        $preselectedSiteId = $request->query('site_id');
        
        if ($isZINARASupervisor) {
            // ZINARA Supervisor can only create ZINARA Clerks
            $sbu3Network = Network::where('name', 'SBU3')->first();
            
            if ($sbu3Network) {
                $networks = Network::where('name', 'SBU3')->get();
                $sites = site::where('sbu', 'SBU3')->orWhere('network_id', $sbu3Network->id)->get();
            } else {
                $networks = collect();
                $sites = collect();
            }
            
            $roles = Role::where('id', 7)->get(); // Only ZINARA Clerk role
        } 
        elseif ($isZINARAClerk) {
            // ZINARA Clerk cannot create users
            return redirect()->route('teams.index')->with('error', 'You do not have permission to create users.');
        } 
        elseif ($roleId == 3) {
            // Regular Supervisor can create regular clerks
            $networks = Network::all();
            $sites = site::all();
            $roles = Role::where('id', 2)->get(); // Only regular Clerk role
        } 
        else {
            // Admin, Super User can create any user
            $networks = Network::all();
            $sites = site::all();
            $roles = Role::all();
        }
        
        return view('teams.create', compact('sites', 'networks', 'roles', 'preselectedSiteId'));
    }
    
    protected function store(Request $request)
    {
        $user = Auth::user();
        $roleId = (int) $user->role_id;
        $isZINARASupervisor = ($roleId == 6);
        $isZINARAClerk = ($roleId == 7);
        
        // Authorization check
        if ($isZINARAClerk) {
            return redirect()->back()->with('error', 'You do not have permission to create users.');
        }
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|exists:roles,id',
            'networkid' => 'required|exists:network,id',
            'siteid' => 'required|exists:site,id',
        ];
        
        // Role-specific restrictions
        if ($isZINARASupervisor) {
            $request->merge(['role' => 7]); // Force ZINARA Clerk role
            
            $sbu3Network = Network::where('name', 'SBU3')->first();
            if ($sbu3Network) {
                $request->merge(['networkid' => $sbu3Network->id]);
            }
        } elseif ($roleId == 3) {
            $request->merge(['role' => 2]); // Force regular Clerk role
        }
        
        $request->validate($rules);
        
        // Create user
        $users = User::create([
            'name' => $request->input('name'),
            'surname' => $request->input('surname') ?? '',
            'role_id' => $request->input('role'),
            'siteid' => $request->input('siteid'),
            'networkid' => $request->input('networkid'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'created_by' => Auth::id(),
        ]);
        
        return Redirect::route('teams.index')
            ->with('success', 'User created successfully.');
    }

    public function sitelist($networkId)
    {
        $sites = site::where('network_id', $networkId)->get();
        return response()->json($sites);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();
        $roleId = (int) $currentUser->role_id;
        
        // Authorization checks
        if ($roleId == 6) { // ZINARA Supervisor
            if ($user->created_by != $currentUser->id || $user->role_id != 7) {
                abort(403, 'Unauthorized access.');
            }
        } elseif ($roleId == 7) { // ZINARA Clerk
            if ($user->id != $currentUser->id) {
                abort(403, 'Unauthorized access.');
            }
        } elseif ($roleId == 3) { // Regular Supervisor
            if ($user->created_by != $currentUser->id && $user->id != $currentUser->id) {
                abort(403, 'Unauthorized access.');
            }
        }
        
        // Get roles based on current user's role
        if ($roleId == 6) {
            $roles = Role::where('id', 7)->get();
            $networks = Network::where('name', 'SBU3')->get();
            $sites = site::where('sbu', 'SBU3')->get();
        } elseif ($roleId == 3) {
            $roles = Role::where('id', 2)->get();
            $networks = Network::all();
            $sites = site::all();
        } else {
            $roles = Role::all();
            $networks = Network::all();
            $sites = site::all();
        }
    
        return view('teams.edit', compact('user', 'roles', 'networks', 'sites'));
    }
    
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();
        $roleId = (int) $currentUser->role_id;
        
        // Authorization checks
        if ($roleId == 6) { // ZINARA Supervisor
            if ($user->created_by != $currentUser->id || $user->role_id != 7) {
                abort(403, 'Unauthorized access.');
            }
        } elseif ($roleId == 7) { // ZINARA Clerk
            if ($user->id != $currentUser->id) {
                abort(403, 'Unauthorized access.');
            }
        } elseif ($roleId == 3) { // Regular Supervisor
            if ($user->created_by != $currentUser->id && $user->id != $currentUser->id) {
                abort(403, 'Unauthorized access.');
            }
        }
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'networkid' => 'required|exists:network,id',
            'siteid' => 'required|exists:site,id',
        ];
        
        if ($roleId == 6) {
            $rules['role'] = 'required|in:7';
        } elseif ($roleId == 3) {
            $rules['role'] = 'required|in:2';
        } elseif ($roleId != 7) {
            $rules['role'] = 'required|exists:roles,id';
        }
        
        $request->validate($rules);
    
        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($roleId != 7) {
            $user->role_id = $request->role;
        }
        
        $user->networkid = $request->networkid;
        $user->siteid = $request->siteid;
    
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
    
        $user->save();
    
        return redirect()->route('teams.index')->with('success', 'User updated successfully!');
    }
    
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();
        $roleId = (int) $currentUser->role_id;
        
        // Authorization checks
        if ($roleId == 6) { // ZINARA Supervisor
            if ($user->created_by != $currentUser->id || $user->role_id != 7) {
                return redirect()->back()->with('error', 'Unauthorized access.');
            }
        } elseif ($roleId == 7) { // ZINARA Clerk
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        } elseif ($roleId == 3) { // Regular Supervisor
            if ($user->created_by != $currentUser->id) {
                return redirect()->back()->with('error', 'Unauthorized access.');
            }
        }
        
        $user->delete();
        
        return redirect()->route('teams.index')->with('success', 'User deleted successfully!');
    }
}
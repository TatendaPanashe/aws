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
    private const COURIER_PLATFORM = 'Courier Connect';

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function courierConnectClerksQuery()
    {
        $siteIds = $this->courierConnectSiteIds();

        return User::with(['role', 'site', 'network'])
            ->where('role_id', 7)
            ->whereIn('siteid', $siteIds);
    }

    private function courierConnectSiteIds()
    {
        return site::whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)])
            ->pluck('id');
    }

    private function courierConnectSiteClerksQuery()
    {
        $siteIds = $this->courierConnectSiteIds();

        return User::with(['role', 'site', 'network'])
            ->whereIn('role_id', [2, 7])
            ->whereIn('siteid', $siteIds);
    }

    private function isCourierConnectClerk(User $user): bool
    {
        $user->loadMissing('site');

        return (int) $user->role_id === 7
            && strtoupper(trim((string) optional($user->site)->platform_name)) === strtoupper(self::COURIER_PLATFORM);
    }

    private function courierConnectSites()
    {
        return site::whereRaw('UPPER(TRIM(platform_name)) = ?', [strtoupper(self::COURIER_PLATFORM)])
            ->orderBy('site_name')
            ->get();
    }

    public function viewCourierClerks()
    {
        abort_unless((int) Auth::user()->role_id === 6, 403);

        $users = $this->courierConnectSiteClerksQuery()
            ->orderBy('name')
            ->get();

        return view('teams.courier-clerks', compact('users'));
    }

    private function networksForSites($sites)
    {
        $networkIds = $sites->pluck('network_id')->filter()->unique()->values();

        return Network::whereIn('id', $networkIds->isNotEmpty() ? $networkIds : [-1])
            ->orderBy('name')
            ->get();
    }

    protected function resetpwd($id)
    {
        $user = User::findOrFail($id);
        
        // Check authorization
        $currentUser = Auth::user();
        $roleId = (int) $currentUser->role_id;
        
        if ($roleId == 6 && !$this->isCourierConnectClerk($user)) {
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
    $isZIMPOSTViewer = ($roleId == 8);
    
    // Debug: Log the current user and role
    \Log::info('===== TEAMS INDEX DEBUG =====');
    \Log::info('Current User ID: ' . $user->id);
    \Log::info('Current User Role ID: ' . $roleId);
    
    if ($isZINARASupervisor) {
        $users = $this->courierConnectClerksQuery()
            ->orderBy('name')
            ->get();
        
        \Log::info('ZINARA Supervisor - Users found: ' . $users->count());
    } 
    elseif ($isZINARAClerk) {
        // ZINARA Clerk - only see themselves
        $users = User::with(['role', 'site', 'network'])
            ->where('id', $user->id)
            ->get();
        \Log::info('ZINARA Clerk - Users found: ' . $users->count());
    } 
    elseif ($isZIMPOSTViewer) {
        // ZIMPOST Viewer - no user management, only show own profile if needed
        $users = User::with(['role', 'site', 'network'])
            ->where('id', $user->id)
            ->get();
        \Log::info('ZIMPOST Viewer - Users found: ' . $users->count());
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
        $isZIMPOSTViewer = ($roleId == 8);
        $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
        
        if ($isZIMPOSTViewer) {
            return redirect()->route('home')->with('error', 'You do not have permission to create users.');
        }
        
        $preselectedSiteId = $request->query('site_id');
        
        if ($isZINARASupervisor) {
            $sites = $this->courierConnectSites();
            $networks = $this->networksForSites($sites);
            
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
        $isZIMPOSTViewer = ($roleId == 8);
        
        // Authorization check
        if ($isZINARAClerk || $isZIMPOSTViewer) {
            return redirect()->back()->with('error', 'You do not have permission to create users.');
        }
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|exists:roles,id',
            'networkid' => 'required|exists:network,id',
            'siteid' => 'required|exists:site,id',
            'zinara_credential' => 'nullable|string|max:255',
            'icecash_credential' => 'nullable|string|max:255',
        ];
        
        // Role-specific restrictions
        if ($isZINARASupervisor) {
            $request->merge(['role' => 7]); // Force ZINARA Clerk role

            $selectedSite = site::find($request->input('siteid'));

            if (!$selectedSite || strtoupper(trim((string) $selectedSite->platform_name)) !== strtoupper(self::COURIER_PLATFORM)) {
                return redirect()->back()->withInput()->with('error', 'Courier clerks must be assigned to a Courier Connect site.');
            }

            $request->merge(['networkid' => $selectedSite->network_id]);
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
            'zinara_credential' => $request->input('zinara_credential'),
            'icecash_credential' => $request->input('icecash_credential'),
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
        
        if ($roleId == 8) { // ZIMPOST Viewer
            abort(403, 'Unauthorized access.');
        }

        // Authorization checks
        if ($roleId == 6) { // ZINARA Supervisor
            if (!$this->isCourierConnectClerk($user)) {
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
            $sites = $this->courierConnectSites();
            $networks = $this->networksForSites($sites);
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
        
        if ($roleId == 8) { // ZIMPOST Viewer
            abort(403, 'Unauthorized access.');
        }

        // Authorization checks
        if ($roleId == 6) { // ZINARA Supervisor
            if (!$this->isCourierConnectClerk($user)) {
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
            'zinara_credential' => 'nullable|string|max:255',
            'icecash_credential' => 'nullable|string|max:255',
        ];
        
        if ($roleId == 6) {
            $rules['role'] = 'required|in:7';

            $selectedSite = site::find($request->input('siteid'));

            if (!$selectedSite || strtoupper(trim((string) $selectedSite->platform_name)) !== strtoupper(self::COURIER_PLATFORM)) {
                return redirect()->back()->withInput()->with('error', 'Courier clerks must be assigned to a Courier Connect site.');
            }

            $request->merge(['networkid' => $selectedSite->network_id]);
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
        $user->zinara_credential = $request->input('zinara_credential');
        $user->icecash_credential = $request->input('icecash_credential');
    
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
        
        if ($roleId == 8) { // ZIMPOST Viewer
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        // Authorization checks
        if ($roleId == 6) { // ZINARA Supervisor
            if (!$this->isSbu3CourierClerk($user)) {
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

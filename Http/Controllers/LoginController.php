<?php

namespace App\Http\Controllers;

use App\Models\login;
use App\Http\Requests\StoreloginRequest;
use App\Http\Requests\UpdateloginRequest;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function login(Request $request)
    {
       
        $credentials = $request->only('email', 'password');
       // dd($credentials);
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('getindex')) 
            ->with('status', 'You have successfully logged in.');

            return redirect()->intended(route('getindex')); // Replace 'dashboard' with your desired route
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login'); // Redirect to the login page after logout
    }
public function get()
{
    return view('login');
}


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('login');
    }

    public function reset(Request $request)
    {
        // Validating the input fields
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'current_password'], // Validates current password
            'confirm_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.current_password' => __('The provided current password does not match our records.'),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Update the user's password
        Auth::user()->update([
            'password' => Hash::make($request->confirm_password)
        ]);

        return back()->with('success', __('Password updated successfully!'));
    }


    /**
     * Show the form for creating a new resource.
     */
   
    

    /**
     * Store a newly created resource in storage.
     */
    public function passchange(Request $request)
    {
        return view('teams.reset');
    }
    
    /**
     * Display the specified resource.
     */
   
   
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(login $login)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateloginRequest $request, login $login)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(login $login)
    {
        //
    }
}

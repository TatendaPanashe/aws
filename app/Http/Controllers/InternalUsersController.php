<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class InternalUsersController extends Controller
{
    //

    public function index()
    {
        return view('login');
    }

public function createUser(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::create([
        'name' => $validatedData['name'],
        'email' => $validatedData['email'],
        'password' => Hash::make($validatedData['password']),
        'role'=> $validatedData['role'],
        'siteid'=> $validatedData['siteid'],
        'networkid'=> $validatedData['networkid'],
    ]);

    return response()->json(['user' => $user], 201);
}   
}

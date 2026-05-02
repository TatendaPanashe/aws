<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Network;
use Illuminate\Support\Facades\Auth;

class NetworkController extends Controller
{
    /**
     * Display a listing of the network resources.
     */
    public function index()
    {
        $networks = Network::all();
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
        $request->validate([
            'name' => 'required',
            'city' => 'required',
            'province' => 'required',
            'description' => 'required',
        ]);

        $network->update($request->all());

        return redirect()->route('networks.index')
                        ->with('success', 'Network updated successfully.');
    }

    /**
     * Remove the specified network from the database.
     */
    public function destroy(Request $request)
    {
       // dd($network);
$network = Network::find($request->input('id'));
        $network->delete();

        return redirect()->route('networks.index')
                        ->with('success', 'Network deleted successfully.');
    }
}
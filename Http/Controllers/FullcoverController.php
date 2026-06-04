<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use App\Models\fullcover;
use App\Http\Requests\StorefullcoverRequest;
use App\Http\Requests\UpdatefullcoverRequest;

class FullcoverController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('fullcover.create');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $sql = fullcover::create([

            
            'date'=> $request->input('date'),
            'currency'=> $request->input('currency'),
            'deposits' => $request->input('deposits'),
            'number_of_policies' =>  $request->input('number_of_policies'),
            'transaction_type' =>$request->input('transaction_type'),
            'user_id' =>Auth::user()->id,
        ]);
            
       //dd($sql);
    
       return Redirect::back();
    }

    /**
     * Display the specified resource.
     */
    public function show(fullcover $fullcover)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $sql = fullcover::all();
        return view('fullcover.manage',compact('sql'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatefullcoverRequest $request, fullcover $fullcover)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(fullcover $fullcover)
    {
        //
    }
}

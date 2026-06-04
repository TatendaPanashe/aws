<?php

namespace App\Http\Controllers;

use App\Models\zinara;
use App\Http\Requests\StorezinaraRequest;
use App\Http\Requests\UpdatezinaraRequest;

class ZinaraController extends Controller
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
        return view('zinara.index');
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
    public function store(StorezinaraRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(zinara $zinara)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(zinara $zinara)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatezinaraRequest $request, zinara $zinara)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(zinara $zinara)
    {
        //
    }
}

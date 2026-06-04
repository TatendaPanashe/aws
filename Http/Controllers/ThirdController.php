<?php

namespace App\Http\Controllers;

use App\Models\third;
use App\Http\Requests\StorethirdRequest;
use App\Http\Requests\UpdatethirdRequest;

class ThirdController extends Controller
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
        return view('third.create');
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
    public function store(StorethirdRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(third $third)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(third $third)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatethirdRequest $request, third $third)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(third $third)
    {
        //
    }
}

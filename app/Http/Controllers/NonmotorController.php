<?php

namespace App\Http\Controllers;

use App\Models\nonmotor;
use App\Http\Requests\StorenonmotorRequest;
use App\Http\Requests\UpdatenonmotorRequest;

class NonmotorController extends Controller
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
        //
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
    public function store(StorenonmotorRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(nonmotor $nonmotor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(nonmotor $nonmotor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatenonmotorRequest $request, nonmotor $nonmotor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(nonmotor $nonmotor)
    {
        //
    }
}

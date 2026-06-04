<?php

namespace App\Http\Controllers;

use App\Models\icecash;
use App\Http\Requests\StoreicecashRequest;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateicecashRequest;

class IcecashController extends Controller
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
        return view('icecash.create');
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
        $sql = icecash::create([

            'amount'=> $request->input('amount'),
            'date'=> $request->input('date'),
            'currency'=> $request->input('currency'),
            'transactions' => $request->input('transactions'),
            'deposits' =>  $request->input('deposits'),
            'transaction_type' =>$request->input('transaction_type'),
            'user_id' =>Auth::user()->id,
        ]);
            
       //dd($sql);
    
       return Redirect::back();
    }

    /**
     * Display the specified resource.
     */
    public function show(icecash $icecash)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(icecash $icecash)
    {
        $sql = icecash::all();
        return view('icecash.manage',compact('sql'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateicecashRequest $request, icecash $icecash)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(icecash $icecash)
    {
        //
    }
    public function search(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $transactionType = $request->input('currency');

        $reports = icecash::filterByDate($startDate, $endDate)
            ->filterByType($transactionType)
            ->get();

        return view('icecash.results', compact('reports'));
    }

    public function downloadPDF(Request $request)
    {
        $startDate = $request->input('created_at');
        $endDate = $request->input('created_at');
        $transactionType = $request->input('currency');

        $reports = icecash::filterByDate($startDate, $endDate)
            ->filterByType($transactionType)
            ->get();

        $pdf = PDF::loadView('icecash.pdf', compact('reports'));
        return $pdf->download('icecash.pdf');
    }
    public function results(Request $request)
    {

        return view('icecash.results');
    }
    public function reports()
    {
        return view('icecash.report');
    }
}

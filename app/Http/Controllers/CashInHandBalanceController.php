<?php

namespace App\Http\Controllers;

use App\Models\CashInHandBalance;
use Illuminate\Http\Request;

class CashInHandBalanceController extends Controller
{
    public function index()
    {
        $balances = CashInHandBalance::all();
        return view('cash_in_hand_balances.index', compact('balances'));
    }

    public function create()
    {
        // If clerks are users, you might want to load them:
        // $clerks = \App\Models\User::where('role','clerk')->get();
        return view('cash_in_hand_balances.create' /*, compact('clerks') */);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'clerk_id' => 'required|integer',
           // 'balance'  => 'required|numeric',
            
        'balance_zwg' => 'required|numeric',
        'balance_usd'=> 'required|numeric',
        ]);

        CashInHandBalance::create($data);

        return redirect()->route('cash-in-hand-balances.index')
                         ->with('success', 'Cash In Hand balance created successfully.');
    }

    public function show(CashInHandBalance $cashInHandBalance)
    {
        return view('cash_in_hand_balances.show', ['balance' => $cashInHandBalance]);
    }

    public function edit(CashInHandBalance $cashInHandBalance)
    {
        // $clerks = \App\Models\User::where('role','clerk')->get();
        return view('cash_in_hand_balances.edit', ['balance' => $cashInHandBalance] /*, compact('clerks') */);
    }

    public function update(Request $request, CashInHandBalance $cashInHandBalance)
    {
        $data = $request->validate([
            'clerk_id' => 'required|integer',
            //'balance'  => 'required|numeric',
              'balance_zwg' => 'required|numeric',
        'balance_usd'=> 'required|numeric',
        ]);

        $cashInHandBalance->update($data);

        return redirect()->route('cash-in-hand-balances.index')
                         ->with('success', 'Cash In Hand balance updated successfully.');
    }

    public function destroy(CashInHandBalance $cashInHandBalance)
    {
        $cashInHandBalance->delete();

        return redirect()->route('cash-in-hand-balances.index')
                         ->with('success', 'Cash In Hand balance deleted successfully.');
    }
}

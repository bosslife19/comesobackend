<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function createTransaction(Request $request)
    {


        $validated = $request->validate([
            'type' => 'required|string',
            'status' => 'required',
            'amount' => 'required',
        ]);

        Transaction::create([
            'type' => $validated['type'],
            'status' => $validated['status'],
            'amount' => $validated['amount'],
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['status' => true], 200);
    }

    public function getAllTransactions(Request $request)
    {
        $transactions = DB::select("SELECT * FROM transactions ORDER BY created_at DESC");

        return response()->json(['transactions' => $transactions], 200);
    }
    public function getTodayTransactions(Request $request)
    {
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $transactions = Transaction::whereBetween('created_at', [$todayStart, $todayEnd])->get();

        return response()->json(['transactions' => $transactions], 200);
    }
}

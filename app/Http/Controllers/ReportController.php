<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\DateRanges;
class ReportController extends Controller
{
    // GET /dashboard?month=YYYY-MM
    use DateRanges;
    public function dashboard(Request $request) {
        $userId = $request->user()->id;
        $ym = $request->query('month'); // YYYY-MM
        [$start, $end] = $this->parseMonth($ym);

        // This month
        $rows = Transaction::select(
                DB::raw("SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) AS income"),
                DB::raw("SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expense")
            )
            ->where('user_id', $userId)
            ->whereBetween('occurred_on', [$start->toDateString(), $end->toDateString()])
            ->first();

        $income  = (float) ($rows->income ?? 0);
        $expense = (float) ($rows->expense ?? 0);
        $remaining = $income - $expense;

        // Current balance up to end of selected month (income - expense)
        $balRows = Transaction::select(
                DB::raw("SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) AS income"),
                DB::raw("SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expense")
            )
            ->where('user_id', $userId)
            ->where('occurred_on', '<=', $end->toDateString())
            ->first();

        $currentBalance = (float) ($balRows->income ?? 0) - (float) ($balRows->expense ?? 0);

        return response()->json([
            'income'           => round($income, 2),
            'expense'          => round($expense, 2),
            'remaining'        => round($remaining, 2),
            'current_balance'  => round($currentBalance, 2),
        ]);
    }
    

    // GET /reports/year/{year}
    public function year(Request $request, int $year) {
         $userId = $request->user()->id;

        // Group by month inside the given year
        $rows = Transaction::query()
            ->selectRaw("DATE_FORMAT(occurred_on, '%Y-%m') as ym")
            ->selectRaw("SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) AS income")
            ->selectRaw("SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expense")
            ->where('user_id', $userId)
            ->whereYear('occurred_on', $year)
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        // Ensure months 01..12 exist (fill missing with zeros)
        $indexed = $rows->keyBy('ym');
        $byMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $ym = sprintf('%04d-%02d', $year, $m);
            $byMonth[] = [
                'ym'     => $ym,
                'income' => round((float) ($indexed[$ym]->income ?? 0), 2),
                'expense'=> round((float) ($indexed[$ym]->expense ?? 0), 2),
            ];
        }

        // Totals from the year only
        $totIncome = array_sum(array_column($byMonth, 'income'));
        $totExpense = array_sum(array_column($byMonth, 'expense'));

        return response()->json([
            'year'     => $year,
            'by_month' => $byMonth,
            'totals'   => [
                'income'    => round($totIncome, 2),
                'expense'   => round($totExpense, 2),
                'remaining' => round($totIncome - $totExpense, 2),
            ],
        ]);
    
    }
}

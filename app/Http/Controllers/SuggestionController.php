<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\DateRanges;

class SuggestionController extends Controller
{
    use DateRanges;

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $ym = $request->query('month'); // YYYY-MM like "2025-10"
        if (!$ym) {
            $ym = now('Asia/Dhaka')->format('Y-m');
        }

        [$start, $end] = $this->parseMonth($ym);
        [$prevStart, $prevEnd] = $this->threeMonthWindowBefore($ym);

        // current month spend by category (only expenses)
        $current = Transaction::query()
            ->join('categories as c', 'c.id', '=', 'transactions.category_id')
            ->where('transactions.user_id', $userId)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.occurred_on', [$start->toDateString(), $end->toDateString()])
            ->groupBy('transactions.category_id', 'c.name')
            ->get([
                'transactions.category_id',
                'c.name as category_name',
                DB::raw('SUM(transactions.amount) as expense_total')
            ])
            ->keyBy('category_id');

        // previous 3-month average per category
        $prevAvg = DB::query()
            ->fromSub(function ($q) use ($userId, $prevStart, $prevEnd) {
                $q->from('transactions')
                    ->selectRaw("category_id, DATE_FORMAT(occurred_on,'%Y-%m') as ym, SUM(amount) as month_sum")
                    ->where('user_id', $userId)
                    ->where('type', 'expense')
                    ->whereBetween('occurred_on', [$prevStart->toDateString(), $prevEnd->toDateString()])
                    ->groupBy('category_id', 'ym');
            }, 't')
            ->select('category_id', DB::raw('AVG(month_sum) as avg_expense'))
            ->groupBy('category_id')
            ->pluck('avg_expense', 'category_id');

        $suggestions = [];
        foreach ($current as $catId => $row) {
            $cur = (float) $row->expense_total;
            $avg = (float) ($prevAvg[$catId] ?? 0);

            if ($avg <= 0) {
                continue; // nothing to compare
            }
            // Flag when current month is at least ৳500 AND > 25% above prior 3mo average
            if ($cur >= 500 && $cur > $avg * 1.25) {
                $suggestions[] = [
                    'category_id'          => (int) $catId,
                    'category_name'        => (string) $row->category_name,
                    'current_month_spend'  => round($cur, 2),
                    'previous_3mo_avg'     => round($avg, 2),
                    'tip'                  => "You spent above your usual in this category. Consider setting a weekly cap, switching to cheaper alternatives, or batching purchases.",
                ];
            }
        }

        // Sort suggestions by largest overshoot first (optional UX nicety)
        usort($suggestions, function ($a, $b) {
            $da = $a['current_month_spend'] - $a['previous_3mo_avg'];
            $db = $b['current_month_spend'] - $b['previous_3mo_avg'];
            return $db <=> $da;
        });

        return response()->json([
            'month'       => $ym,
            'suggestions' => array_values($suggestions),
        ]);
    }
}

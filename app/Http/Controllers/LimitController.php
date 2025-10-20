<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LimitController extends Controller
{
    // GET /limits/monthly?month=YYYY-MM
    public function getMonthly(Request $request)
    {
        $userId = Auth::id();
        $month = $request->query('month', now()->format('Y-m'));

        $row = DB::table('monthly_limits')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->first();

        return response()->json([
            'limit' => $row?->limit ?? null,
        ]);
    }

    // POST /limits/monthly
    // body: { "month": "2025-10", "limit": 25000 }
    public function setMonthly(Request $request)
    {
        $userId = Auth::id();

        // validate
        $v = Validator::make($request->all(), [
            'month' => ['nullable', 'regex:/^\d{4}\-(0[1-9]|1[0-2])$/'],
            'limit' => ['required', 'numeric', 'min:0'],
        ], [
            'month.regex' => 'Month must be in YYYY-MM format.',
        ]);

        if ($v->fails()) {
            return response()->json(['status' => 422, 'errors' => $v->errors()], 422);
        }

        $month = $request->input('month', now()->format('Y-m'));
        $limit = (float) $request->input('limit', 0);

        DB::table('monthly_limits')->updateOrInsert(
            ['user_id' => $userId, 'month' => $month],
            ['limit' => $limit, 'updated_at' => now(), 'created_at' => now()]
        );

        // OPTIONAL: return current status so UI can refresh immediately
        $status = $this->computeStatus($userId, $month);

        return response()->json([
            'status' => 200,
            'msg'    => 'Monthly limit saved successfully',
            'data'   => $status,
        ]);
    }

    // GET /limits/monthly/status?month=YYYY-MM
    // returns: { month, limit, spent, remaining, over_by, is_over, pct_used, top_categories: [...] }
    public function status(Request $request)
    {
        $userId = Auth::id();
        $month = $request->query('month', now()->format('Y-m'));

        $status = $this->computeStatus($userId, $month);

        return response()->json([
            'status' => 200,
            'data'   => $status,
        ]);
    }

    /**
     * Core calculator used by setMonthly() and status()
     */
    private function computeStatus(int $userId, string $month): array
    {
        // month range
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        // fetch limit (nullable)
        $limitRow = DB::table('monthly_limits')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->first();

        $limit = $limitRow?->limit;

        // --- total SPENT in that month ---
        // Handle both schemas:
        //  A) amount > 0 means expense
        //  B) there is a 'type' column and type='expense'
        // We’ll try to detect by checking if `type` column exists (cheap try/catch-less way using information_schema costs; simpler: OR both).
        $spent = DB::table('transactions')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($q) {
                $q->where('amount', '>', 0)             // Case A: positive amount is expense
                  ->orWhere('type', '=', 'expense');     // Case B: explicit type
            })
            ->sum('amount');

        $spent = (float) $spent;

        // --- Top categories ---
        // Join with categories if exists; if not, name = "Category #ID"
        $categoriesTableExists = $this->tableExists('categories');

        $catQuery = DB::table('transactions as t')
            ->select([
                't.category_id',
                DB::raw('SUM(t.amount) as amount')
            ])
            ->where('t.user_id', $userId)
            ->whereBetween('t.created_at', [$start, $end])
            ->where(function ($q) {
                $q->where('t.amount', '>', 0)->orWhere('t.type', '=', 'expense');
            })
            ->groupBy('t.category_id')
            ->orderByDesc(DB::raw('SUM(t.amount)'))
            ->limit(4);

        $topRaw = $catQuery->get();

        $topCategories = $topRaw->map(function ($r) use ($categoriesTableExists) {
            $name = null;
            if ($categoriesTableExists && $r->category_id !== null) {
                $c = DB::table('categories')->where('id', $r->category_id)->first();
                $name = $c?->name;
            }
            return [
                'category_id' => $r->category_id,
                'name' => $name ?: 'Category #' . ($r->category_id ?? 'N/A'),
                'amount' => (float) $r->amount,
            ];
        })->values()->all();

        // --- status math ---
        $hasLimit = is_numeric($limit) && $limit > 0;
        $remaining = $hasLimit ? max(0, (float)$limit - $spent) : null;
        $overBy = $hasLimit && $spent > (float)$limit ? $spent - (float)$limit : 0.0;
        $pctUsed = $hasLimit && (float)$limit > 0 ? round(($spent / (float)$limit) * 100, 2) : null;

        return [
            'month'         => $month,
            'limit'         => $hasLimit ? (float)$limit : null,
            'spent'         => $spent,
            'remaining'     => $remaining,  // null if no limit
            'over_by'       => $overBy,     // 0 if not over, else positive
            'is_over'       => $hasLimit ? $spent > (float)$limit : false,
            'pct_used'      => $pctUsed,    // null if no limit
            'top_categories'=> $topCategories,
            // Helper messages (optional, useful for UI)
            'message'       => $this->buildMessage($hasLimit ? (float)$limit : null, $spent, $topCategories),
        ];
    }

    private function buildMessage(?float $limit, float $spent, array $topCategories): string
    {
        if (!$limit || $limit <= 0) {
            return 'No monthly limit set.';
        }
        if ($spent <= $limit) {
            $remaining = $limit - $spent;
            return "On track. Remaining ৳ " . number_format($remaining, 2) . ".";
        }
        $over = $spent - $limit;
        $top = collect($topCategories)->take(3)->pluck('name')->implode(', ');
        return "Limit crossed by ৳ " . number_format($over, 2) . ($top ? " (big areas: $top)" : "");
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

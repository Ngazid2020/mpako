<?php

namespace App\Http\Controllers\Api\Commerce;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController
{
    public function summary(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $shopId = $shop->id;
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $todaySales = DB::table('sales')
            ->where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereDate('created_at', $today);

        $monthSales = DB::table('sales')
            ->where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$monthStart . ' 00:00:00', now()->toDateTimeString()]);

        $todayExpenses = DB::table('expenses')
            ->where('shop_id', $shopId)
            ->whereDate('spent_at', $today)
            ->sum('amount');

        $monthExpenses = DB::table('expenses')
            ->where('shop_id', $shopId)
            ->whereBetween('spent_at', [$monthStart, $today])
            ->sum('amount');

        $creditsCollected = DB::table('credit_payments')
            ->join('credits', 'credits.id', '=', 'credit_payments.credit_id')
            ->where('credits.shop_id', $shopId)
            ->whereBetween('credit_payments.paid_at', [$monthStart . ' 00:00:00', now()->toDateTimeString()])
            ->sum('credit_payments.amount');

        $pendingCredits = DB::table('credits')
            ->where('shop_id', $shopId)
            ->where('status', '!=', 'paid')
            ->selectRaw('count(*) as count, sum(total_amount) as total_amount')
            ->first();

        $pendingPurchases = DB::table('purchases')
            ->where('shop_id', $shopId)
            ->where('status', 'pending')
            ->selectRaw('count(*) as count, sum(total_amount) as total_amount')
            ->first();

        $supplierDebt = DB::table('suppliers')
            ->where('shop_id', $shopId)
            ->sum('balance');

        $customerDebt = DB::table('customers')
            ->where('shop_id', $shopId)
            ->sum('balance');

        $overdueCreditsCount = DB::table('credits')
            ->where('shop_id', $shopId)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', $today)
            ->count();

        return response()->json([
            'today' => [
                'sales_count' => (clone $todaySales)->count(),
                'revenue'     => (float) (clone $todaySales)->sum('total_amount'),
                'expenses'    => (float) $todayExpenses,
            ],
            'this_month' => [
                'sales_count'       => (clone $monthSales)->count(),
                'revenue'           => (float) (clone $monthSales)->sum('total_amount'),
                'expenses'          => (float) $monthExpenses,
                'credits_collected' => (float) $creditsCollected,
            ],
            'pending_credits' => [
                'count'        => (int) ($pendingCredits->count ?? 0),
                'total_amount' => (float) ($pendingCredits->total_amount ?? 0),
            ],
            'pending_purchases' => [
                'count'        => (int) ($pendingPurchases->count ?? 0),
                'total_amount' => (float) ($pendingPurchases->total_amount ?? 0),
            ],
            'supplier_debt'          => (float) $supplierDebt,
            'customer_debt'          => (float) $customerDebt,
            'overdue_credits_count'  => (int) $overdueCreditsCount,
        ]);
    }

    public function salesByDay(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $shopId = $shop->id;
        $days = min(30, max(7, (int) $request->query('days', 14)));

        $rows = DB::table('sales')
            ->selectRaw('date(created_at) as date, count(*) as count, sum(total_amount) as revenue')
            ->where('shop_id', $shopId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('date(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            if (isset($rows[$date])) {
                $result[] = [
                    'date'    => $date,
                    'count'   => (int) $rows[$date]->count,
                    'revenue' => (float) $rows[$date]->revenue,
                ];
            } else {
                $result[] = [
                    'date'    => $date,
                    'count'   => 0,
                    'revenue' => 0.0,
                ];
            }
        }

        return response()->json($result);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $shopId = $shop->id;
        $limit = min(10, max(5, (int) $request->query('limit', 8)));

        $products = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->selectRaw('si.product_name, SUM(si.quantity) as total_qty, SUM(si.subtotal) as total_revenue')
            ->where('s.shop_id', $shopId)
            ->where('s.status', 'completed')
            ->where('s.created_at', '>=', now()->subDays(30))
            ->groupBy('si.product_name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_name'  => $row->product_name,
                'total_qty'     => (float) $row->total_qty,
                'total_revenue' => (float) $row->total_revenue,
            ]);

        return response()->json($products);
    }

    public function expensesByCategory(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $shopId = $shop->id;
        $monthStart = now()->startOfMonth()->toDateString();

        $categories = DB::table('expenses as e')
            ->leftJoin('expense_categories as ec', 'ec.id', '=', 'e.expense_category_id')
            ->selectRaw("COALESCE(ec.name, 'Sans catégorie') as category_name, SUM(e.amount) as total")
            ->where('e.shop_id', $shopId)
            ->where('e.spent_at', '>=', $monthStart)
            ->groupBy('ec.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category_name' => $row->category_name,
                'total'         => (float) $row->total,
            ]);

        return response()->json($categories);
    }
}

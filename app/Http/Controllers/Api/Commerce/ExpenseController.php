<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Api\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use InteractsWithShop;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'view_any_expense');

        $expenses = $this->shop($request)->expenses()->with('category')->latest()->limit(200)->get();

        return response()->json(ExpenseResource::collection($expenses));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'create_expense');

        $shop = $this->shop($request);

        $request->validate([
            'expense_category_id' => ['nullable', 'integer', $this->inShop('expense_categories', $shop)],
            'description'         => 'nullable|string|max:255',
            'amount'              => 'required|numeric|min:1',
            'spent_at'            => 'nullable|date',
            'note'                => 'nullable|string|max:500',
        ]);

        $expense = Expense::create([
            'shop_id'             => $shop->id,
            'user_id'             => $request->user()->id,
            'expense_category_id' => $request->expense_category_id,
            // description est NOT NULL en base
            'description'         => $request->description ?: 'Dépense',
            'amount'              => $request->amount,
            'spent_at'            => $request->spent_at ?? now()->toDateString(),
            'note'                => $request->note,
        ]);

        return response()->json(new ExpenseResource($expense->load('category')), 201);
    }
}
